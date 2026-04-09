<?php

namespace Platform\ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Platform\ExperienceCms\Contracts\PageVersionRestoreContract;
use Platform\ExperienceCms\Models\Page;
use Platform\ExperienceCms\Models\PageVersion;

class PageVersionController extends Controller
{
    public function __construct(protected PageVersionRestoreContract $restore) {}

    public function restore(Page $platformPage, PageVersion $platformVersion): RedirectResponse
    {
        abort_unless($platformVersion->page_id === $platformPage->getKey(), 404);

        $this->restore->restore($platformPage, $platformVersion);

        return redirect()
            ->route('admin.cms.pages.edit', $platformPage)
            ->with('success', sprintf('Version %d restored to the current draft.', $platformVersion->version_number));
    }
}
