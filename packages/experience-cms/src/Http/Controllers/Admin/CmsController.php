<?php

namespace Platform\ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class CmsController extends Controller
{
    public function index(): View
    {
        return view('experience-cms::admin.cms.index', [
            'cardGroups' => $this->managementCardGroups(),
            'createPageUrl' => $this->routeUrl('admin.cms.pages.create'),
        ]);
    }

    private function managementCardGroups(): array
    {
        $groups = [
            'Content Management' => [
                [
                    'title' => 'Pages',
                    'description' => 'Create and manage storefront pages.',
                    'route' => 'admin.cms.pages.index',
                ],
                [
                    'title' => 'Content Entries',
                    'description' => 'Manage reusable structured content.',
                    'route' => 'admin.cms.content-entries.index',
                ],
                [
                    'title' => 'Menus',
                    'description' => 'Manage website navigation menus.',
                    'route' => 'admin.cms.menus.index',
                ],
            ],
            'Layout Management' => [
                [
                    'title' => 'Header',
                    'description' => 'Configure storefront header content and layout.',
                    'route' => 'admin.cms.header-configs.index',
                ],
                [
                    'title' => 'Footer',
                    'description' => 'Configure storefront footer content and layout.',
                    'route' => 'admin.cms.footer-configs.index',
                ],
                [
                    'title' => 'Assignments',
                    'description' => 'Assign templates and content to storefront areas.',
                    'route' => 'admin.cms.assignments.index',
                ],
            ],
            'CMS Structure' => [
                [
                    'title' => 'Templates',
                    'description' => 'Manage page templates and reusable layouts.',
                    'route' => 'admin.cms.templates.index',
                ],
                [
                    'title' => 'Section Types',
                    'description' => 'Define available CMS section structures.',
                    'route' => 'admin.cms.section-types.index',
                ],
                [
                    'title' => 'Component Types',
                    'description' => 'Define reusable CMS component structures.',
                    'route' => 'admin.cms.component-types.index',
                ],
            ],
            'Configuration' => [
                [
                    'title' => 'Site Settings',
                    'description' => 'Manage global website and SEO settings.',
                    'route' => 'admin.cms.site-settings.index',
                ],
            ],
        ];

        return array_values(array_filter(array_map(function (string $groupTitle, array $cards): ?array {
            $cards = array_values(array_filter(array_map(function (array $card): ?array {
                $url = $this->routeUrl($card['route']);

                if (! $url) {
                    return null;
                }

                return [
                    ...$card,
                    'url' => $url,
                ];
            }, $cards)));

            if (empty($cards)) {
                return null;
            }

            return [
                'title' => $groupTitle,
                'cards' => $cards,
            ];
        }, array_keys($groups), $groups)));
    }

    private function routeUrl(string $routeName): ?string
    {
        if (! Route::has($routeName)) {
            return null;
        }

        return route($routeName);
    }
}
