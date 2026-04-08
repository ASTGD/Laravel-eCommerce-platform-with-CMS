<?php

declare(strict_types=1);

namespace ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use ExperienceCms\Models\Menu;
use ExperienceCms\Models\Page;
use ExperienceCms\Models\SectionType;
use ExperienceCms\Models\Template;
use ExperienceCms\Models\ThemePreset;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('experience-cms::admin.dashboard.index', [
            'stats' => [
                'pages' => Page::query()->count(),
                'templates' => Template::query()->count(),
                'sectionTypes' => SectionType::query()->count(),
                'themePresets' => ThemePreset::query()->count(),
                'menus' => Menu::query()->count(),
            ],
        ]);
    }
}
