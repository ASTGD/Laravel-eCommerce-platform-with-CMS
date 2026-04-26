<?php

namespace Platform\CommerceCore\Services\Affiliates;

use InvalidArgumentException;
use Platform\CommerceCore\Models\AffiliateProfile;

class AffiliateReferralLinkService
{
    public function __construct(protected AffiliateSettingsService $affiliateSettingsService) {}

    public function build(AffiliateProfile $profile, ?string $target = null): string
    {
        $target = $this->normalizeInternalTarget($target);
        $target = $this->withoutExistingReferralParameter($target);
        $url = url($target);
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.rawurlencode($this->affiliateSettingsService->referralParameter()).'='.rawurlencode($profile->referral_code);
    }

    public function normalizeInternalTarget(?string $target = null): string
    {
        $target = trim((string) $target);

        if ($target === '') {
            return '/';
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $target) === 1) {
            throw new InvalidArgumentException('Enter a valid internal storefront path.');
        }

        if (str_starts_with($target, '//')) {
            throw new InvalidArgumentException('Referral links can only point to this storefront.');
        }

        if (str_starts_with($target, '#')) {
            throw new InvalidArgumentException('Enter a page path before adding a referral link.');
        }

        if (preg_match('#^https?://#i', $target) === 1) {
            $target = $this->sameStorePathFromUrl($target);
        }

        if (! str_starts_with($target, '/')) {
            $target = '/'.$target;
        }

        $path = parse_url($target, PHP_URL_PATH) ?: '/';
        $query = parse_url($target, PHP_URL_QUERY);
        $adminPrefix = trim((string) config('app.admin_url', 'admin'), '/');

        if ($adminPrefix !== '' && preg_match('#^/'.preg_quote($adminPrefix, '#').'(/|$)#', $path) === 1) {
            throw new InvalidArgumentException('Referral links cannot point to admin pages.');
        }

        return $query ? "{$path}?{$query}" : $path;
    }

    protected function sameStorePathFromUrl(string $target): string
    {
        $store = parse_url(url('/'));
        $url = parse_url($target);

        if (! is_array($url) || ! isset($url['host'])) {
            throw new InvalidArgumentException('Enter a valid storefront URL.');
        }

        $sameHost = strcasecmp((string) ($store['host'] ?? ''), (string) $url['host']) === 0;
        $sameScheme = ! isset($url['scheme']) || strcasecmp((string) ($store['scheme'] ?? 'http'), (string) $url['scheme']) === 0;
        $samePort = (int) ($store['port'] ?? 0) === (int) ($url['port'] ?? 0);

        if (! $sameHost || ! $sameScheme || ! $samePort) {
            throw new InvalidArgumentException('Referral links can only point to this storefront.');
        }

        $path = $url['path'] ?? '/';
        $query = isset($url['query']) ? '?'.$url['query'] : '';

        return $path.$query;
    }

    protected function withoutExistingReferralParameter(string $target): string
    {
        $path = parse_url($target, PHP_URL_PATH) ?: '/';
        $query = parse_url($target, PHP_URL_QUERY);

        if (! $query) {
            return $path;
        }

        parse_str($query, $parameters);

        unset($parameters[$this->affiliateSettingsService->referralParameter()]);

        $query = http_build_query($parameters);

        return $query !== '' ? "{$path}?{$query}" : $path;
    }
}
