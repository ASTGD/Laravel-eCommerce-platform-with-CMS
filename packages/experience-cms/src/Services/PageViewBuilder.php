<?php

declare(strict_types=1);

namespace ExperienceCms\Services;

use ExperienceCms\Models\FooterConfig;
use ExperienceCms\Models\HeaderConfig;
use ExperienceCms\Models\Menu;
use ExperienceCms\Models\Page;
use ExperienceCms\Models\SiteSetting;
use ThemeCore\Contracts\SectionRendererContract;
use ThemeCore\Contracts\ThemePresetResolverContract;

class PageViewBuilder
{
    public function __construct(
        private readonly SectionRendererContract $sectionRenderer,
        private readonly ThemePresetResolverContract $themePresetResolver,
    ) {}

    public function build(Page $page, bool $preview = false): array
    {
        $page->loadMissing('seoMeta', 'template', 'sections.sectionType', 'sections.components.componentType');

        $header = $this->resolveHeader();
        $footer = $this->resolveFooter();

        return [
            'page' => $page,
            'preset' => $this->themePresetResolver->resolve(),
            'header' => $header,
            'footer' => $footer,
            'primaryMenu' => $this->resolveMenu($header?->settings_json['primary_menu_code'] ?? null),
            'footerMenu' => $this->resolveMenu($footer?->settings_json['footer_menu_code'] ?? null),
            'renderedSections' => $page->sections
                ->where('is_active', true)
                ->map(fn ($section): string => $this->sectionRenderer->make($section, [
                    'page' => $page,
                    'preview' => $preview,
                ])->render())
                ->all(),
            'preview' => $preview,
        ];
    }

    private function resolveHeader(): ?HeaderConfig
    {
        return HeaderConfig::query()
            ->where('code', SiteSetting::valueFor('header.active_code'))
            ->orWhere('is_default', true)
            ->first();
    }

    private function resolveFooter(): ?FooterConfig
    {
        return FooterConfig::query()
            ->where('code', SiteSetting::valueFor('footer.active_code'))
            ->orWhere('is_default', true)
            ->first();
    }

    private function resolveMenu(?string $code): ?Menu
    {
        if ($code === null || $code === '') {
            return null;
        }

        return Menu::query()
            ->where('code', $code)
            ->with(['items' => fn ($query) => $query->whereNull('parent_id')->with('children')])
            ->first();
    }
}
