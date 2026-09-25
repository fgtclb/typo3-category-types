<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Backend\FormEngine;

use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Offers the category types of one group as the items of a backend select field, so that a
 * setting can name category types without a hard-coded item list that misses the types a
 * project adds and keeps the ones it removes.
 *
 * The group is part of the field configuration, which core hands to an `itemsProcFunc` in
 * whole, a custom `itemsProcConfig` included - for a TCA column and a FlexForm field alike:
 *
 * ```php
 * 'config' => [
 *     'type' => 'select',
 *     'renderType' => 'selectMultipleSideBySide',
 *     'itemsProcFunc' => \FGTCLB\CategoryTypes\Backend\FormEngine\CategoryTypeItemsProcFunc::class . '->itemsForGroup',
 *     'itemsProcConfig' => ['group' => 'programs'],
 * ],
 * ```
 *
 * Public, because core instantiates an `itemsProcFunc` through
 * `GeneralUtility::makeInstance()`, which takes only public services from the container.
 */
#[Autoconfigure(public: true)]
final readonly class CategoryTypeItemsProcFunc
{
    public function __construct(
        private CategoryTypeRegistry $categoryTypeRegistry,
    ) {}

    /**
     * Appends one item per category type of the group, in registry order: the title as the
     * label, the identifier as the value and the icon the extension registers for the type.
     *
     * The title is passed on as registered. FormEngine translates the label of every item
     * after the provider ran, and `CategoryTypes.yaml` takes a literal title as well as a
     * label reference.
     *
     * A field without a group, or with one no active extension registers, gets no items
     * rather than an error: an installation can deactivate the extension that registers the
     * group while the field naming it stays configured.
     *
     * @param array<string, mixed> $params
     */
    public function itemsForGroup(array &$params): void
    {
        $config = is_array($params['config'] ?? null) ? $params['config'] : [];
        $itemsProcConfig = is_array($config['itemsProcConfig'] ?? null) ? $config['itemsProcConfig'] : [];
        $group = $itemsProcConfig['group'] ?? '';
        if (!is_string($group) || $group === '') {
            return;
        }

        $items = is_array($params['items'] ?? null) ? $params['items'] : [];
        foreach ($this->categoryTypeRegistry->getGroupedCategoryTypes()[$group] ?? [] as $categoryType) {
            $items[] = [
                'label' => $categoryType->getTitle(),
                'value' => $categoryType->getIdentifier(),
                'icon' => $categoryType->getIconIdentifier(),
            ];
        }
        $params['items'] = $items;
    }
}
