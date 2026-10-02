<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\ViewHelpers;

use FGTCLB\CategoryTypes\Collection\FilterCollection;
use FGTCLB\CategoryTypes\Domain\Model\Category;
use FGTCLB\CategoryTypes\Filter\CategoryFilterNormalizer;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * The category filter of a list URL, as one comma separated uid list, optionally without
 * one category: the filter argument of a link that removes that category from the
 * selection and keeps every other one.
 *
 * ```html
 * <html xmlns:ct="http://typo3.org/ns/FGTCLB/CategoryTypes/ViewHelpers" data-namespace-typo3-fluid="true">
 *
 * <f:variable name="remaining" value="{ct:filterArgument(filterCollection: demand.filterCollection, without: category)}" />
 * ```
 *
 * The value is the one {@see CategoryFilterNormalizer::toFilterArgument()} builds for the
 * redirect of a filter submission, so a link and a submitted form lead to the same URL. An
 * empty string means that nothing is left to filter by. A list leaves the filter argument
 * out then, because a list route enhancer cannot generate an empty filter.
 */
final class FilterArgumentViewHelper extends AbstractViewHelper
{
    public function __construct(
        private readonly CategoryFilterNormalizer $categoryFilterNormalizer,
    ) {}

    public function initializeArguments(): void
    {
        // Neither is required: a list without any filter has no filter collection, and a
        // template of a project may hand in nothing at all.
        $this->registerArgument('filterCollection', FilterCollection::class, 'The filter collection of the demand.', false, null);
        $this->registerArgument('without', Category::class, 'The category to leave out.', false, null);
    }

    public function render(): string
    {
        $filterCollection = $this->arguments['filterCollection'] ?? null;
        $without = $this->arguments['without'] ?? null;

        return $this->categoryFilterNormalizer->toFilterArgument(
            $filterCollection instanceof FilterCollection ? $filterCollection : null,
            $without instanceof Category ? $without->getUid() : null,
        );
    }
}
