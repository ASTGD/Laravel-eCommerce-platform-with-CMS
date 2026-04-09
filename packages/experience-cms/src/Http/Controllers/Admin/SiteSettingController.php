<?php

namespace Platform\ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Platform\ExperienceCms\Http\Requests\Admin\SiteSettingRequest;
use Platform\ExperienceCms\Models\SiteSetting;

class SiteSettingController extends Controller
{
    public function index(): View
    {
        return view('experience-cms::admin.site-settings.index', [
            'siteSettings' => SiteSetting::query()->orderBy('group')->orderBy('key')->get(),
        ]);
    }

    public function create(): View
    {
        return view('experience-cms::admin.site-settings.form', [
            'siteSetting' => new SiteSetting(['group' => 'store']),
        ]);
    }

    public function store(SiteSettingRequest $request): RedirectResponse
    {
        $siteSetting = SiteSetting::query()->updateOrCreate(
            ['key' => $request->payload()['key']],
            $request->payload()
        );

        return redirect()
            ->route('admin.cms.site-settings.edit', $siteSetting)
            ->with('success', 'Site setting saved.');
    }

    public function edit(SiteSetting $platformSiteSetting): View
    {
        return view('experience-cms::admin.site-settings.form', [
            'siteSetting' => $platformSiteSetting,
        ]);
    }

    public function update(SiteSettingRequest $request, SiteSetting $platformSiteSetting): RedirectResponse
    {
        $platformSiteSetting->update($request->payload());

        return redirect()
            ->route('admin.cms.site-settings.edit', $platformSiteSetting)
            ->with('success', 'Site setting updated.');
    }
}
