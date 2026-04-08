<?php

namespace Platform\ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Platform\ExperienceCms\Http\Requests\Admin\HeaderConfigRequest;
use Platform\ExperienceCms\Models\HeaderConfig;

class HeaderConfigController extends Controller
{
    public function index(): View
    {
        return view('experience-cms::admin.header-configs.index', [
            'headerConfigs' => HeaderConfig::query()->orderByDesc('is_default')->orderBy('code')->get(),
        ]);
    }

    public function create(): View
    {
        return view('experience-cms::admin.header-configs.form', [
            'headerConfig' => new HeaderConfig(['is_default' => false]),
        ]);
    }

    public function store(HeaderConfigRequest $request): RedirectResponse
    {
        $headerConfig = HeaderConfig::query()->create($request->payload());

        $this->syncDefault($headerConfig);

        return redirect()
            ->route('admin.cms.header-configs.edit', $headerConfig)
            ->with('success', 'Header config created.');
    }

    public function edit(HeaderConfig $headerConfig): View
    {
        return view('experience-cms::admin.header-configs.form', compact('headerConfig'));
    }

    public function update(HeaderConfigRequest $request, HeaderConfig $headerConfig): RedirectResponse
    {
        $headerConfig->update($request->payload());

        $this->syncDefault($headerConfig);

        return redirect()
            ->route('admin.cms.header-configs.edit', $headerConfig)
            ->with('success', 'Header config updated.');
    }

    public function destroy(HeaderConfig $headerConfig): RedirectResponse
    {
        $headerConfig->delete();

        return redirect()
            ->route('admin.cms.header-configs.index')
            ->with('success', 'Header config deleted.');
    }

    protected function syncDefault(HeaderConfig $headerConfig): void
    {
        if (! $headerConfig->is_default) {
            return;
        }

        HeaderConfig::query()
            ->whereKeyNot($headerConfig->getKey())
            ->update(['is_default' => false]);
    }
}
