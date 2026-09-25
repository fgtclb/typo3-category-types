<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Filter;

use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Decides which category filters a list offers, and in which order, from the configured
 * filter types and the categories of the listed records.
 *
 * The collection is the one `CategoryRepository::findAllApplicable()` returns: every
 * category of every type of the group, those on none of the listed records marked
 * disabled. A filter renders for a type with at least one category, disabled or not, so the
 * resolver drops every other one here - a type without any category, and an identifier that
 * is not a type of the group (any more). What is left is what the list renders, which is
 * why a visible count is applied after that and counts only filters that render.
 *
 * Without configured types, the list offers every type with a category in the type order
 * of the group, which is what it did before filter types could be configured.
 *
 * @internal Shared by the list plugins of the academic extensions; not part of the public API
 *           of this extension. A project changes the filters through the setting, not here.
 */
final readonly class FilterTypeResolver
{
    /**
     * @param string $categoryTypes Comma-separated type identifiers, in the order to offer them; empty for all.
     * @param int $visibleCount How many filters to show right away, the rest go to `more`; 0 or less for all.
     */
    public function resolve(CategoryCollection $categories, string $categoryTypes, int $visibleCount = 0): FilterTypes
    {
        $available = [];
        foreach ($categories->getAllCategoriesByType() as $typeIdentifier => $typeCategories) {
            if ($typeCategories !== []) {
                $available[] = (string)$typeIdentifier;
            }
        }

        $configured = array_values(array_unique(GeneralUtility::trimExplode(',', $categoryTypes, true)));
        $offered = $configured === []
            ? $available
            : array_values(array_filter(
                $configured,
                static fn(string $typeIdentifier): bool => in_array($typeIdentifier, $available, true),
            ));

        if ($visibleCount <= 0) {
            return new FilterTypes($offered, []);
        }

        return new FilterTypes(
            array_slice($offered, 0, $visibleCount),
            array_slice($offered, $visibleCount),
        );
    }
}
