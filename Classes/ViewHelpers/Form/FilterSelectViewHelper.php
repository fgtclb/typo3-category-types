<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\ViewHelpers\Form;

use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

class FilterSelectViewHelper extends AbstractSelectViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('groupByParent', 'bool', 'If true, options will be grouped by parents.', false, false);
        $this->registerArgument('groupLevelClassPrefix', 'string', 'Prefix of the level indicator class for grouped options.', false, 'level-');
    }

    /**
     * @return array<int, mixed>
     */
    protected function getOptions(): array
    {
        if (!is_array($this->arguments['options'])
            && !$this->arguments['options'] instanceof \Traversable
        ) {
            return [];
        }

        $options = [];
        $optionsFromArgument = $this->arguments['options'];

        foreach ($optionsFromArgument as $option) {
            $value = $this->optionProperty($option, 'optionValueField') ?? (string)$option->getUid();
            $options[] = [
                'label' => $this->optionProperty($option, 'optionLabelField') ?? (string)$option->getTitle(),
                'value' => $value,
                'uid' => $option->getUid(),
                'parentId' => $option->getParentId(),
                'isRoot' => $option->isRoot(),
                'type' => (string)$option->getType(),
                'isSelected' => $this->isSelected($value),
                'isDisabled' => $option->isDisabled(),
                'level' => 0,
                'children' => [],
            ];
        }

        if ($this->arguments['sortByOptionLabel'] !== false) {
            usort($options, fn($a, $b) => strcoll((string)$a['label'], (string)$b['label']));
        }

        if ($this->arguments['groupByParent'] !== false) {
            $optionsTree = [];
            foreach ($options as $option) {
                if ($option['isRoot'] === true) {
                    $option['children'] = $this->createOptionsTree($options, $option);
                    $optionsTree[] = $option;
                }
            }

            $options = [];
            $options = $this->linearizeOptionsTree($options, $optionsTree);
        }

        // Added last: the level is only known once the options were grouped, and the base
        // class writes the markup from this array.
        foreach ($options as $key => $option) {
            $attributes = ['class' => $this->arguments['groupLevelClassPrefix'] . $option['level']];
            if ($option['isDisabled'] === true) {
                $attributes['disabled'] = 'disabled';
            }
            $options[$key]['attributes'] = $attributes;
        }

        return $options;
    }

    /**
     * Resolves `optionValueField` or `optionLabelField` on a single option, and returns
     * `null` when the argument was not given so the caller can fall back to the getter the
     * category filter uses by default.
     */
    private function optionProperty(mixed $option, string $argumentName): ?string
    {
        if (!$this->hasArgument($argumentName)) {
            return null;
        }

        $property = ObjectAccess::getPropertyPath($option, (string)$this->arguments[$argumentName]);
        if (!is_scalar($property) && !$property instanceof \Stringable && $property !== null) {
            throw new \RuntimeException(
                sprintf(
                    'Property "%s" of "%s", read through "%s", cannot be cast to string.',
                    (string)$this->arguments[$argumentName],
                    get_debug_type($option),
                    $argumentName,
                ),
                1785706600,
            );
        }

        return (string)$property;
    }

    /**
     * Create the options tree
     *
     * @param array<int, mixed> $options
     * @param array<string, mixed> $parent
     * @return array<int, mixed>
     */
    private function createOptionsTree(&$options, $parent): array
    {
        $tree = [];
        foreach ($options as $option) {
            if ($option['parentId'] == $parent['uid']) {
                $child = $option;
                $child['level'] = $parent['level'] + 1;
                array_push($tree, $child);

                $subTree = $this->createOptionsTree($options, $child);
                if ($subTree !== []) {
                    foreach ($subTree as $option) {
                        array_push($tree, $option);
                    }
                }
            }
        }
        return $tree;
    }

    /**
     * Linearize the options tree
     *
     * @param array<int, mixed> $options
     * @param array<int, mixed> $optionsTree
     * @return array<int, mixed>
     */
    private function linearizeOptionsTree(array &$options, array $optionsTree): array
    {
        foreach ($optionsTree as $key => $option) {
            array_push($options, $option);
            if ($option['children'] !== []) {
                $this->linearizeOptionsTree($options, $option['children']);
            }
        }
        return $options;
    }
}
