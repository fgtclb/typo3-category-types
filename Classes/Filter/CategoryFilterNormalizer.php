<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Filter;

use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Collection\FilterCollection;
use FGTCLB\CategoryTypes\Domain\Model\Category;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Turns the category filter of a submitted demand form into a list of category uids,
 * and a resolved filter back into the one argument a list URL carries.
 *
 * The filter reaches a controller action as a plain array taken from the request, without
 * any validation on the way, so every shape has to be survivable. A value that cannot be
 * read is treated as no filter rather than as an error - a crafted request must not be
 * able to take a list plugin down.
 *
 * The shapes that do carry a selection:
 *
 * - a single select submits one uid per category type,
 * - a select with `multiple` submits one value per selected option,
 * - a value assembled by a template may be a comma separated list.
 *
 * The category type the values were submitted under is not part of the result. The
 * consumers pass the list to
 * {@see \FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository::findByGroupAndUidList()},
 * which resolves the types of the whole group anyway.
 */
class CategoryFilterNormalizer
{
    /**
     * The one key of the filter in a list URL, see {@see toFilterArgument()}.
     */
    private const URL_ARGUMENT = 'categories';

    /**
     * @return array<int, int>
     */
    public function toUidList(mixed $filter): array
    {
        if (!is_array($filter)) {
            return [];
        }

        $uids = [];
        foreach ($filter as $filterValue) {
            foreach ($this->toStringList($filterValue) as $value) {
                $uids = array_merge($uids, GeneralUtility::intExplode(',', $value, true));
            }
        }

        return array_values(array_unique($uids));
    }

    /**
     * Whether the preset categories of a content element take part in a demand whose
     * category filter is `$filter`: always where the element hides the filter, and for a
     * filter keyed by category type, the shape of a filter form, which keeps the presets of
     * the types it does not name. A filter in the shape of a list URL, the one key
     * `categories`, and a demand without a filter are the whole selection.
     *
     * The list plugins of the academic extensions apply the presets of a request without
     * any demand themselves, this answers for a request with one.
     *
     * @internal Shared by the demand factories of the academic list plugins.
     */
    public function keepsPresets(mixed $filter, bool $filterHidden): bool
    {
        return $filterHidden || (is_array($filter) && !array_key_exists(self::URL_ARGUMENT, $filter));
    }

    /**
     * {@see toUidList()} with the preset categories the filter keeps, see
     * {@see keepsPresets()}: every preset where the element hides the filter, so a
     * submission or a link can narrow the list but never leave the presets, and otherwise
     * the presets of every category type the filter does not name, so a form that does not
     * offer a type, a program finder or a list whose filter types leave it out, does not
     * drop its preset. A type the filter names, also with an empty "all" value, replaces
     * its presets.
     *
     * @param array<string, array<array-key, Category>> $presetCategoriesByType The presets
     *        by category type identifier, as {@see CategoryCollection::getAllCategoriesByType()}
     *        returns them.
     * @return array<int, int>
     * @internal Shared by the demand factories of the academic list plugins.
     */
    public function toUidListKeepingPresets(mixed $filter, array $presetCategoriesByType, bool $filterHidden): array
    {
        $uids = [];
        if ($this->keepsPresets($filter, $filterHidden)) {
            $namedTypes = $filterHidden || !is_array($filter) ? [] : array_map(strval(...), array_keys($filter));
            foreach ($presetCategoriesByType as $typeIdentifier => $categories) {
                if (in_array((string)$typeIdentifier, $namedTypes, true)) {
                    continue;
                }
                foreach ($categories as $category) {
                    $uids[] = $category->getUid();
                }
            }
        }

        return array_values(array_unique(array_merge($uids, $this->toUidList($filter))));
    }

    /**
     * The reverse of {@see toUidList()}: the categories of a resolved filter as one comma
     * separated uid list, the shape a list plugin puts into the URL it redirects to.
     *
     * The list is in ascending uid order, so one selection has exactly one URL whichever
     * order its categories were selected in. The category type is left out for the reason
     * {@see toUidList()} ignores it. An empty string means that nothing is filtered.
     *
     * `$withoutCategory` leaves the category of that uid out, which is the argument of a
     * link that removes one category from the selection and keeps the others.
     */
    public function toFilterArgument(?FilterCollection $filterCollection, ?int $withoutCategory = null): string
    {
        if ($filterCollection === null) {
            return '';
        }

        $uids = [];
        foreach ($filterCollection->getFilterCategories() as $category) {
            if ($category->getUid() !== $withoutCategory) {
                $uids[] = $category->getUid();
            }
        }
        sort($uids, SORT_NUMERIC);

        return implode(',', array_unique($uids));
    }

    /**
     * @return array<int, string>
     */
    private function toStringList(mixed $filterValue): array
    {
        if (!is_array($filterValue)) {
            return $this->toStringOrNull($filterValue) === null ? [] : [(string)$filterValue];
        }

        $values = [];
        foreach ($filterValue as $value) {
            $stringValue = $this->toStringOrNull($value);
            if ($stringValue !== null) {
                $values[] = $stringValue;
            }
        }

        return $values;
    }

    private function toStringOrNull(mixed $value): ?string
    {
        if (is_bool($value) || $value === null) {
            return null;
        }
        if (is_scalar($value) || $value instanceof \Stringable) {
            return (string)$value;
        }

        return null;
    }
}
