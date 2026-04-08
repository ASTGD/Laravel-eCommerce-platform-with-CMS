<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_admin_can_sign_in_and_access_dashboard(): void
    {
        $this->seed();

        $response = $this->post('/login', [
            'email' => config('platform.admin.email'),
            'password' => config('platform.admin.password'),
        ]);

        $response->assertRedirect(route('admin.dashboard'));

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Milestone 1 foundation with the first CMS/theme vertical slice.');
    }
}
