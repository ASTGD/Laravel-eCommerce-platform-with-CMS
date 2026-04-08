<?php

declare(strict_types=1);

namespace Tests\Unit\Theme;

use ExperienceCms\Services\SectionRegistry;
use Tests\TestCase;

class SectionRegistryTest extends TestCase
{
    public function test_built_in_section_registry_contains_expected_sections(): void
    {
        $registry = $this->app->make(SectionRegistry::class);

        $this->assertCount(8, $registry->all());
        $this->assertNotNull($registry->find('hero_banner'));
        $this->assertNotNull($registry->find('featured_products'));
        $this->assertNotNull($registry->find('rich_text'));
    }
}
