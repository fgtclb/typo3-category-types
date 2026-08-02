<?php

declare(strict_types=1);

namespace TESTS\CategoryTypesGroup\ViewHelpers;

use FGTCLB\CategoryTypes\ViewHelpers\Form\AbstractSelectViewHelper;

/**
 * Minimal subclass turning the `options` argument into the option shape
 * `AbstractSelectViewHelper::renderOptionTags()` expects, the way the
 * `SortingSelectViewHelper` of `EXT:academic_partners`, `EXT:academic_programs` and
 * `EXT:academic_projects` does.
 *
 * `ViewHelpers\Form\FilterSelectViewHelper` overrides both `getOptions()` and
 * `renderOptionTags()`, so the ones of the base class cannot be reached through it.
 */
final class TestSelectViewHelper extends AbstractSelectViewHelper
{
    /**
     * @return array<array<string, mixed>>
     */
    protected function getOptions(): array
    {
        $options = [];
        foreach ((array)($this->arguments['options'] ?? []) as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label,
                'isSelected' => $this->isSelected((string)$value),
            ];
        }

        return $options;
    }
}
