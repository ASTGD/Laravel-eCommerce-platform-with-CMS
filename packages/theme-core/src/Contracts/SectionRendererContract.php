<?php

namespace Platform\ThemeCore\Contracts;

interface SectionRendererContract
{
    public function render(string $view, array $data = []): string;
}
