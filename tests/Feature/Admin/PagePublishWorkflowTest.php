<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use ExperienceCms\Enums\PageStatus;
use ExperienceCms\Enums\PageType;
use ExperienceCms\Models\Page;
use ExperienceCms\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagePublishWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_publish_route_creates_a_version_snapshot(): void
    {
        $this->seed();

        $admin = User::query()->where('email', config('platform.admin.email'))->firstOrFail();
        $template = Template::query()->firstOrFail();

        $page = Page::query()->create([
            'title' => 'Campaign',
            'slug' => 'campaign',
            'type' => PageType::Campaign->value,
            'template_id' => $template->id,
            'status' => PageStatus::Draft->value,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pages.publish', $page))
            ->assertRedirect(route('admin.pages.edit', $page));

        $page->refresh();

        $this->assertSame(PageStatus::Published->value, $page->status->value);
        $this->assertDatabaseCount('page_versions', 2);
        $this->assertDatabaseHas('page_versions', [
            'page_id' => $page->id,
            'version_number' => 1,
        ]);
    }
}
