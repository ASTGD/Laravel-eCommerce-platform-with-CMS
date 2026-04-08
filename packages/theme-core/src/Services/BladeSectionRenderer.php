<?php

namespace Platform\ThemeCore\Services;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Platform\ThemeCore\Contracts\SectionRendererContract;

class BladeSectionRenderer implements SectionRendererContract
{
    public function __construct(protected ViewFactory $view) {}

    public function render(string $view, array $data = []): string
    {
        return $this->view->make($view, $data)->render();
    }
}
