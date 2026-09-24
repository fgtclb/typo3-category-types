<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Filter;

use FGTCLB\CategoryTypes\Collection\FilterCollection;
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
     * The reverse of {@see toUidList()}: the categories of a resolved filter as one comma
     * separated uid list, the shape a list plugin puts into the URL it redirects to.
     *
     * The list is in ascending uid order, so one selection has exactly one URL whichever
     * order its categories were selected in. The category type is left out for the reason
     * {@see toUidList()} ignores it. An empty string means that nothing is filtered.
     */
    public function toFilterArgument(?FilterCollection $filterCollection): string
    {
        if ($filterCollection === null) {
            return '';
        }

        $uids = [];
        foreach ($filterCollection->getFilterCategories() as $category) {
            $uids[] = $category->getUid();
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
