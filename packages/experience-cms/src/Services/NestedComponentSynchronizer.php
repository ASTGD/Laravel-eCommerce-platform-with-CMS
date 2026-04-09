<?php

namespace Platform\ExperienceCms\Services;

use Platform\ExperienceCms\Models\ComponentType;
use Platform\ExperienceCms\Models\PageSection;
use Platform\ExperienceCms\Models\SectionComponent;

class NestedComponentSynchronizer
{
    public function __construct(protected ComponentTypeRegistry $componentTypes) {}

    public function sync(PageSection $section, array $components): void
    {
        $existing = $section->components()->get()->keyBy('id');
        $seenIds = [];

        foreach ($components as $componentData) {
            $component = $componentData['id']
                ? $existing->get($componentData['id'], new SectionComponent(['page_section_id' => $section->getKey()]))
                : new SectionComponent(['page_section_id' => $section->getKey()]);

            $definition = null;

            if ($componentData['component_type_id']) {
                $code = optional($component->componentType)->code;

                if (! $code || (int) $component->component_type_id !== (int) $componentData['component_type_id']) {
                    $code = (string) optional(ComponentType::query()->find($componentData['component_type_id']))?->code;
                }

                if ($code) {
                    $definition = $this->componentTypes->find($code);
                }
            }

            $component->fill([
                'component_type_id' => $componentData['component_type_id'],
                'sort_order' => $componentData['sort_order'],
                'settings_json' => array_replace($definition?->defaultConfig() ?? [], $componentData['settings_json']),
                'visibility_rules_json' => [],
                'data_source_type' => $componentData['data_source_type'],
                'data_source_payload_json' => $componentData['data_source_payload_json'],
                'is_active' => $componentData['is_active'],
            ])->save();

            $seenIds[] = $component->getKey();
        }

        $section->components()
            ->when($seenIds !== [], fn ($query) => $query->whereNotIn('id', $seenIds), fn ($query) => $query)
            ->delete();
    }
}
