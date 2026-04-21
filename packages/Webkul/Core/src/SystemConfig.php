<?php

namespace Webkul\Core;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\Core\SystemConfig\Item;

class SystemConfig
{
    /**
     * Items array.
     */
    public array $items = [];

    /**
     * Cached config rows grouped by scope for the current request.
     *
     * @var array<string, \Illuminate\Support\Collection<string, \Webkul\Core\Models\CoreConfig>>
     */
    protected array $coreConfigScopeCache = [];

    /**
     * Create a new class instance.
     *
     * @return void
     */
    public function __construct(protected CoreConfigRepository $coreConfigRepository) {}

    /**
     * Add Item.
     */
    public function addItem(Item $item): void
    {
        $this->items[] = $item;
    }

    /**
     * Get all configuration items.
     */
    public function getItems(): Collection
    {
        if (! $this->items) {
            $this->prepareConfigurationItems();
        }

        return collect($this->items)
            ->sortBy('sort');
    }

    /**
     * Retrieve Core Config
     */
    private function retrieveCoreConfig(): array
    {
        static $items;

        if ($items) {
            return $items;
        }

        return $items = config('core');
    }

    /**
     * Prepare configuration items.
     */
    public function prepareConfigurationItems()
    {
        $configWithDotNotation = [];

        foreach ($this->retrieveCoreConfig() as $item) {
            $configWithDotNotation[$item['key']] = $item;
        }

        $configs = Arr::undot(Arr::dot($configWithDotNotation));

        foreach ($configs as $configItem) {
            $subConfigItems = $this->processSubConfigItems($configItem);

            $this->addItem(new Item(
                children: $subConfigItems,
                fields: $configItem['fields'] ?? null,
                icon: $configItem['icon'] ?? null,
                icon_class: $configItem['icon_class'] ?? null,
                info: trans($configItem['info']) ?? null,
                key: $configItem['key'],
                name: trans($configItem['name']),
                route: $configItem['route'] ?? null,
                sort: $configItem['sort'],
            ));
        }
    }

    /**
     * Process sub config items.
     */
    private function processSubConfigItems($configItem): Collection
    {
        return collect($configItem)
            ->sortBy('sort')
            ->filter(fn ($value) => is_array($value) && isset($value['name']))
            ->map(function ($subConfigItem) {
                $configItemChildren = $this->processSubConfigItems($subConfigItem);

                return new Item(
                    children: $configItemChildren,
                    fields: $subConfigItem['fields'] ?? null,
                    icon: $subConfigItem['icon'] ?? null,
                    icon_class: $subConfigItem['icon_class'] ?? null,
                    info: trans($subConfigItem['info']) ?? null,
                    key: $subConfigItem['key'],
                    name: trans($subConfigItem['name']),
                    route: $subConfigItem['route'] ?? null,
                    sort: $subConfigItem['sort'] ?? null,
                );
            });
    }

    /**
     * Get active configuration item.
     */
    public function getActiveConfigurationItem(): ?Item
    {
        if (! $slug = request()->route('slug')) {
            return null;
        }

        $activeItem = $this->getItems()->where('key', $slug)->first() ?? null;

        if (! $activeItem) {
            return null;
        }

        if ($slug2 = request()->route('slug2')) {
            $activeItem = $activeItem->getChildren()[$slug2];
        }

        return $activeItem;
    }

    /**
     * Get config field.
     */
    public function getConfigField(string $fieldName): ?array
    {
        foreach ($this->retrieveCoreConfig() as $coreData) {
            if (! isset($coreData['fields'])) {
                continue;
            }

            foreach ($coreData['fields'] as $field) {
                $name = $coreData['key'].'.'.$field['name'];

                if ($name == $fieldName) {
                    return $field;
                }
            }
        }

        return null;
    }

    /**
     * Get core config values.
     */
    protected function getCoreConfig(string $field, ?string $channel, ?string $locale): ?CoreConfig
    {
        $fields = $this->getConfigField($field);

        if (! $fields) {
            return null;
        }

        return $this->getScopedCoreConfigs(
            channel: ! empty($fields['channel_based']) ? $channel : null,
            locale: ! empty($fields['locale_based']) ? $locale : null,
        )->get($field);
    }

    /**
     * Load all config rows for a given scope once per request.
     *
     * @return \Illuminate\Support\Collection<string, \Webkul\Core\Models\CoreConfig>
     */
    protected function getScopedCoreConfigs(?string $channel, ?string $locale): Collection
    {
        $cacheKey = implode('|', [
            $channel ?? '__null__',
            $locale ?? '__null__',
        ]);

        if (isset($this->coreConfigScopeCache[$cacheKey])) {
            return $this->coreConfigScopeCache[$cacheKey];
        }

        $query = CoreConfig::query();

        if ($channel) {
            $query->where('channel_code', $channel);
        } else {
            $query->whereNull('channel_code');
        }

        if ($locale) {
            $query->where('locale_code', $locale);
        } else {
            $query->whereNull('locale_code');
        }

        return $this->coreConfigScopeCache[$cacheKey] = $query
            ->get()
            ->keyBy('code');
    }

    /**
     * Get default config.
     */
    protected function getDefaultConfig(string $field): mixed
    {
        $configFieldInfo = $this->getConfigField($field);

        $fields = explode('.', $field);

        array_shift($fields);

        $field = implode('.', $fields);

        return Config::get($field, $configFieldInfo['default'] ?? null);
    }

    /**
     * Get the config data.
     */
    public function getConfigData(string $field, ?string $currentChannelCode = null, ?string $currentLocaleCode = null): mixed
    {
        if (empty($currentChannelCode)) {
            $currentChannelCode = core()->getRequestedChannelCode();
        }

        if (empty($currentLocaleCode)) {
            $currentLocaleCode = core()->getRequestedLocaleCode();
        }

        $coreConfig = $this->getCoreConfig($field, $currentChannelCode, $currentLocaleCode);

        if (! $coreConfig) {
            return $this->getDefaultConfig($field);
        }

        return $coreConfig->value;
    }
}
