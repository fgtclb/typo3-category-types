<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\ViewHelpers;

use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Collection\FilterCollection;
use FGTCLB\CategoryTypes\Domain\Model\Category;
use PHPUnit\Framework\Attributes\Test;

/**
 * `ViewHelpers\FilterArgumentViewHelper` hands a list template the filter argument of a
 * link that removes one category from the selection. The partner, project and program
 * lists render their active filter tags with it.
 *
 * The fixture template brackets the value, so an empty result is visible as `[]`.
 */
final class FilterArgumentViewHelperTest extends AbstractViewHelperTestCase
{
    #[Test]
    public function aListWithoutFilterHasNoFilterArgument(): void
    {
        $this->assertSame('[]', $this->render('FilterArgument', ['filterCollection' => null, 'without' => null]));
    }

    #[Test]
    public function oneCategoryIsItsUid(): void
    {
        $this->assertSame('[12]', $this->render('FilterArgument', [
            'filterCollection' => $this->filterCollection(12),
            'without' => null,
        ]));
    }

    /**
     * The categories in ascending order, the shape of the URL a filter submission is
     * redirected to.
     */
    #[Test]
    public function withoutACategoryEveryCategoryIsKept(): void
    {
        $this->assertSame('[5,12,31]', $this->render('FilterArgument', [
            'filterCollection' => $this->filterCollection(31, 5, 12),
            'without' => null,
        ]));
    }

    #[Test]
    public function theCategoryToLeaveOutIsRemoved(): void
    {
        $filterCollection = $this->filterCollection(31, 5, 12);

        $this->assertSame('[5,31]', $this->render('FilterArgument', [
            'filterCollection' => $filterCollection,
            'without' => $this->category($filterCollection, 12),
        ]));
    }

    /**
     * Removing the only category leaves nothing to filter by.
     */
    #[Test]
    public function removingTheOnlyCategoryLeavesNoFilterArgument(): void
    {
        $filterCollection = $this->filterCollection(12);

        $this->assertSame('[]', $this->render('FilterArgument', [
            'filterCollection' => $filterCollection,
            'without' => $this->category($filterCollection, 12),
        ]));
    }

    private function filterCollection(int ...$uids): FilterCollection
    {
        $categoryCollection = new CategoryCollection();
        foreach ($uids as $uid) {
            $categoryCollection->attach(new Category(uid: $uid, parentId: 0, title: 'Category ' . $uid));
        }

        return new FilterCollection($categoryCollection);
    }

    private function category(FilterCollection $filterCollection, int $uid): Category
    {
        foreach ($filterCollection->getFilterCategories() as $category) {
            if ($category->getUid() === $uid) {
                return $category;
            }
        }
        $this->fail('The filter collection has no category ' . $uid);
    }
}
