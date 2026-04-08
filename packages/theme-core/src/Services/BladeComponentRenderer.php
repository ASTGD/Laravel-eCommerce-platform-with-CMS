<?php

namespace Platform\ThemeCore\Services;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Platform\ThemeCore\Contracts\ComponentRendererContract;

class BladeComponentRenderer implements ComponentRendererContract
{
    public function __construct(protected ViewFactory $view) {}

    public function render(string $view, array $data = []): string
    {
        return $this->view->make($view, $data)->render();
    }
}
