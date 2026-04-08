<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_homepage_renders_key_sections(): void
    {
        $this->seed();

        $this->get('/')
            ->assertOk()
            ->assertSee('Deploy the same commerce platform to many independent storefronts.')
            ->assertSee('Featured products')
            ->assertSee('Canvas Weekender');
    }
}
