<?php

declare(strict_types=1);

namespace ThemeCore\Services;

use ExperienceCms\Models\PageSection;
use ThemeCore\Contracts\DataSourceResolverContract;

class DataSourceResolver implements DataSourceResolverContract
{
    public function resolve(PageSection $section, array $context = []): array
    {
        $payload = $section->data_source_payload_json ?? [];
        $settings = $section->settings_json ?? [];

        return match ($section->data_source_type) {
            'manual_products', 'manual_categories', 'selected_content_entries' => $payload['items'] ?? $settings['items'] ?? [],
            'featured_products', 'best_sellers', 'new_arrivals', 'discounted_products' => $payload['items'] ?? $settings['items'] ?? [],
            default => $settings['items'] ?? [],
        };
    }
}
