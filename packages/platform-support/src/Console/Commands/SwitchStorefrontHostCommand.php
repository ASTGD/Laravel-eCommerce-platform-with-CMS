<?php

namespace Platform\PlatformSupport\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Models\Channel;

class SwitchStorefrontHostCommand extends Command
{
    protected $signature = 'platform:storefront-host
        {target : "local" for 127.0.0.1, or a host/full URL like 192.168.1.136:8001}
        {--channel= : Channel code to update. Defaults to the app channel code.}
        {--env-file= : Optional env file path. Defaults to the project .env file.}';

    protected $description = 'Synchronize APP_URL, channel hostname, and local storefront links for localhost or LAN development.';

    public function handle(): int
    {
        try {
            $channel = $this->resolveChannel();
            $envFile = $this->resolveEnvFile();
            $targetUrl = $this->normalizeTarget((string) $this->argument('target'));
            $previousHostname = (string) ($channel->hostname ?: config('app.url'));

            $this->updateEnvUrl($envFile, $targetUrl);

            $channel->forceFill([
                'hostname' => $targetUrl,
            ])->save();

            $updatedFooterTranslations = $this->normalizeFooterLinks(
                channelId: (int) $channel->id,
                previousHostname: $previousHostname,
                targetUrl: $targetUrl,
            );

            $this->callSilent('optimize:clear');

            $this->components->info("Storefront host switched to {$targetUrl}");
            $this->line("Channel: {$channel->code}");
            $this->line("Env file: {$envFile}");
            $this->line("Footer link translations normalized: {$updatedFooterTranslations}");

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    protected function resolveChannel(): Channel
    {
        $channelCode = $this->option('channel') ?: config('app.channel');

        $channel = Channel::query()
            ->where('code', $channelCode)
            ->first();

        if (! $channel) {
            throw new \RuntimeException("Channel [{$channelCode}] was not found.");
        }

        return $channel;
    }

    protected function resolveEnvFile(): string
    {
        $envFile = $this->option('env-file') ?: base_path('.env');

        if (! is_file($envFile)) {
            throw new \RuntimeException("Env file [{$envFile}] was not found.");
        }

        return $envFile;
    }

    protected function normalizeTarget(string $target): string
    {
        $target = trim($target);

        if ($target === '') {
            throw new \InvalidArgumentException('The target host cannot be empty.');
        }

        if (in_array(strtolower($target), ['local', 'localhost'], true)) {
            $target = '127.0.0.1';
        }

        if (! str_contains($target, '://')) {
            $target = 'http://'.$target;
        }

        $parts = parse_url($target);

        if (! is_array($parts) || empty($parts['host'])) {
            throw new \InvalidArgumentException("The target [{$target}] is not a valid URL or host.");
        }

        $scheme = $parts['scheme'] ?? 'http';
        $host = $parts['host'];
        $port = $parts['port'] ?? $this->defaultPort();

        return sprintf('%s://%s:%d', $scheme, $host, $port);
    }

    protected function defaultPort(): int
    {
        $configuredUrl = parse_url((string) config('app.url'));

        if (is_array($configuredUrl) && isset($configuredUrl['port'])) {
            return (int) $configuredUrl['port'];
        }

        return (int) (env('APP_PORT', 8001));
    }

    protected function updateEnvUrl(string $envFile, string $targetUrl): void
    {
        $contents = file_get_contents($envFile);

        if ($contents === false) {
            throw new \RuntimeException("Unable to read env file [{$envFile}].");
        }

        $updatedContents = preg_replace(
            '/^APP_URL=.*$/m',
            'APP_URL='.$targetUrl,
            $contents,
            1,
            $count
        );

        if ($updatedContents === null) {
            throw new \RuntimeException("Unable to update APP_URL in [{$envFile}].");
        }

        if ($count === 0) {
            $updatedContents = rtrim($contents).PHP_EOL.'APP_URL='.$targetUrl.PHP_EOL;
        }

        file_put_contents($envFile, $updatedContents);
    }

    protected function normalizeFooterLinks(int $channelId, string $previousHostname, string $targetUrl): int
    {
        $footerCustomizationIds = DB::table('theme_customizations')
            ->where('channel_id', $channelId)
            ->where('type', 'footer_links')
            ->pluck('id');

        if ($footerCustomizationIds->isEmpty()) {
            return 0;
        }

        $translations = DB::table('theme_customization_translations')
            ->whereIn('theme_customization_id', $footerCustomizationIds)
            ->get(['id', 'options']);

        $updatedCount = 0;

        foreach ($translations as $translation) {
            $options = json_decode($translation->options, true);

            if (! is_array($options)) {
                continue;
            }

            $hasChanges = false;

            foreach ($options as $column => $links) {
                if (! is_array($links)) {
                    continue;
                }

                foreach ($links as $index => $link) {
                    if (! is_array($link) || empty($link['url'])) {
                        continue;
                    }

                    $normalizedUrl = $this->normalizeFooterLinkUrl(
                        url: (string) $link['url'],
                        previousHostname: $previousHostname,
                        targetUrl: $targetUrl,
                    );

                    if ($normalizedUrl !== $link['url']) {
                        $options[$column][$index]['url'] = $normalizedUrl;
                        $hasChanges = true;
                    }
                }
            }

            if (! $hasChanges) {
                continue;
            }

            DB::table('theme_customization_translations')
                ->where('id', $translation->id)
                ->update([
                    'options' => json_encode($options, JSON_UNESCAPED_SLASHES),
                ]);

            $updatedCount++;
        }

        return $updatedCount;
    }

    protected function normalizeFooterLinkUrl(string $url, string $previousHostname, string $targetUrl): string
    {
        if ($url === '' || str_starts_with($url, '/')) {
            return $url;
        }

        $parsedUrl = parse_url($url);

        if (! is_array($parsedUrl) || empty($parsedUrl['host']) || empty($parsedUrl['path'])) {
            return $url;
        }

        $currentOrigin = $this->canonicalOrigin($url);
        $previousOrigin = $this->canonicalOrigin($previousHostname);
        $targetOrigin = $this->canonicalOrigin($targetUrl);

        if (! in_array($currentOrigin, [$previousOrigin, $targetOrigin], true)) {
            return $url;
        }

        $relative = $parsedUrl['path'];

        if (! empty($parsedUrl['query'])) {
            $relative .= '?'.$parsedUrl['query'];
        }

        if (! empty($parsedUrl['fragment'])) {
            $relative .= '#'.$parsedUrl['fragment'];
        }

        return $relative;
    }

    protected function canonicalOrigin(string $url): ?string
    {
        if (! str_contains($url, '://')) {
            $url = 'http://'.$url;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $scheme = $parts['scheme'] ?? 'http';
        $host = $parts['host'];
        $port = $parts['port'] ?? $this->defaultPort();

        return sprintf('%s://%s:%d', $scheme, $host, $port);
    }
}
