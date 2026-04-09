<?php

namespace Platform\ExperienceCms\Services;

use Platform\ExperienceCms\Models\Template;
use Platform\ExperienceCms\Models\TemplateArea;

class TemplateSchemaSynchronizer
{
    public function sync(Template $template, array $areas): Template
    {
        $seenCodes = [];

        foreach ($areas as $area) {
            TemplateArea::query()->updateOrCreate(
                [
                    'template_id' => $template->getKey(),
                    'code' => $area['code'],
                ],
                [
                    'name' => $area['name'],
                    'rules_json' => $area['rules_json'],
                    'sort_order' => $area['sort_order'],
                ]
            );

            $seenCodes[] = $area['code'];
        }

        $template->areas()
            ->when($seenCodes !== [], fn ($query) => $query->whereNotIn('code', $seenCodes), fn ($query) => $query)
            ->delete();

        return $template->fresh('areas');
    }
}
