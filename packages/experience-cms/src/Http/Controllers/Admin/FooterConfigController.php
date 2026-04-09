<?php

namespace Platform\ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Platform\ExperienceCms\Http\Requests\Admin\FooterConfigRequest;
use Platform\ExperienceCms\Models\FooterConfig;

class FooterConfigController extends Controller
{
    public function index(): View
    {
        return view('experience-cms::admin.footer-configs.index', [
            'footerConfigs' => FooterConfig::query()->orderByDesc('is_default')->orderBy('code')->get(),
        ]);
    }

    public function create(): View
    {
        return view('experience-cms::admin.footer-configs.form', [
            'footerConfig' => new FooterConfig(['is_default' => false]),
        ]);
    }

    public function store(FooterConfigRequest $request): RedirectResponse
    {
        $footerConfig = FooterConfig::query()->create($request->payload());

        $this->syncDefault($footerConfig);

        return redirect()
            ->route('admin.cms.footer-configs.edit', $footerConfig)
            ->with('success', 'Footer config created.');
    }

    public function edit(FooterConfig $platformFooterConfig): View
    {
        return view('experience-cms::admin.footer-configs.form', [
            'footerConfig' => $platformFooterConfig,
        ]);
    }

    public function update(FooterConfigRequest $request, FooterConfig $platformFooterConfig): RedirectResponse
    {
        $platformFooterConfig->update($request->payload());

        $this->syncDefault($platformFooterConfig);

        return redirect()
            ->route('admin.cms.footer-configs.edit', $platformFooterConfig)
            ->with('success', 'Footer config updated.');
    }

    public function destroy(FooterConfig $platformFooterConfig): RedirectResponse
    {
        $platformFooterConfig->delete();

        return redirect()
            ->route('admin.cms.footer-configs.index')
            ->with('success', 'Footer config deleted.');
    }

    protected function syncDefault(FooterConfig $footerConfig): void
    {
        if (! $footerConfig->is_default) {
            return;
        }

        FooterConfig::query()
            ->whereKeyNot($footerConfig->getKey())
            ->update(['is_default' => false]);
    }
}
