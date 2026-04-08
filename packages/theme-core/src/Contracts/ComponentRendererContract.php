<?php

namespace Platform\ThemeCore\Contracts;

interface ComponentRendererContract
{
    public function render(string $view, array $data = []): string;
}
