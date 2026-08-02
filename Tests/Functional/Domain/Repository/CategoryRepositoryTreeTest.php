<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\Domain\Repository;

use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use FGTCLB\CategoryTypes\Tests\Functional\AbstractCategoryTypesTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Direct coverage for the two methods walking the category tree:
 * `CategoryRepository::findParent()` and `getCategoryRootline()`.
 *
 * `findParent()` is reached from `Category::getParent()` and therefore from `isRoot()`,
 * which decides where an option is placed in the grouped filter select - see
 * `Tests/Functional/Domain/Model/CategoryTest`.
 *
 * `getCategoryRootline()` has no caller in this repository: the only one,
 * `EXT:academic_programs` `Backend\Tca\Labels::category()`, is commented out. It is public
 * API nonetheless, and the recursion is the part worth pinning down - the more so because
 * the method returns raw database rows rather than `Category` objects, unlike every other
 * method of the class.
 *
 * `EXT:category_types` registers no category group of its own, so the group and the two
 * types come from the `test_category_types_group` fixture extension.
 */
final class CategoryRepositoryTreeTest extends AbstractCategoryTypesTestCase
{
    protected function setUp(): void
    {
        $this->addTestExtension(...array_values([
            'tests/category-types-group',
        ]));
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/CategoryRepository/categoryTree.csv');
    }

    /**
     * The argument is the uid to look up, not the uid whose parent is wanted - the name
     * describes the caller, `Category::getParent()`, which passes its own `parentId`.
     */
    #[Test]
    public function categoryIsFoundByItsUid(): void
    {
        $category = $this->subject()->findParent('testing', 1);

        $this->assertNotNull($category);
        $this->assertSame('Root Category', $category->getTitle());
        $this->assertSame(1, $category->getUid());
    }

    #[Test]
    public function foundCategoryCarriesItsTypeOfTheRequestedGroup(): void
    {
        $category = $this->subject()->findParent('testing', 2);

        $this->assertNotNull($category);
        $this->assertSame('testing_first', (string)$category->getType());
        $this->assertSame(1, $category->getParentId());
    }

    #[Test]
    public function unknownCategoryIsNotFound(): void
    {
        $this->assertNull($this->subject()->findParent('testing', 999));
    }

    /**
     * No restriction is lifted here, so a hidden category has no parent from the frontend's
     * point of view - which makes `Category::isRoot()` report its children as roots.
     */
    #[Test]
    public function hiddenCategoryIsNotFound(): void
    {
        $this->assertNull($this->subject()->findParent('testing', 4));
    }

    /**
     * Unlike every other method of the repository this one does not check the group: the
     * type of the found record is resolved against the group that was passed, so a category
     * of a foreign group comes back without a type rather than not at all.
     */
    #[Test]
    public function categoryOfAnotherGroupIsFoundWithoutItsType(): void
    {
        $category = $this->subject()->findParent('unknown', 1);

        $this->assertNotNull($category);
        $this->assertSame('Root Category', $category->getTitle());
        $this->assertNull($category->getType());
    }

    #[Test]
    public function rootlineOfANestedCategoryStartsAtTheRoot(): void
    {
        $rootline = $this->subject()->getCategoryRootline(3);

        $this->assertSame(
            ['Root Category', 'Child Category', 'Grandchild Category'],
            array_column($rootline, 'title'),
        );
    }

    #[Test]
    public function rootlineCarriesTheFullDatabaseRows(): void
    {
        $rootline = $this->subject()->getCategoryRootline(2);

        $this->assertSame([1, 2], array_map(intval(...), array_column($rootline, 'uid')));
        $this->assertSame(['testing_first', 'testing_first'], array_column($rootline, 'type'));
    }

    #[Test]
    public function rootlineOfARootCategoryHoldsThatCategoryOnly(): void
    {
        $rootline = $this->subject()->getCategoryRootline(1);

        $this->assertCount(1, $rootline);
        $this->assertSame('Root Category', $rootline[0]['title']);
    }

    /**
     * The rootline is built from the raw records, so it does not stop at a hidden ancestor
     * the way `findParent()` does.
     */
    #[Test]
    public function rootlineIncludesHiddenAncestors(): void
    {
        $rootline = $this->subject()->getCategoryRootline(5);

        $this->assertSame(['Hidden Root Category', 'Child Of Hidden Root'], array_column($rootline, 'title'));
    }

    /**
     * Neither does it stop where the type changes, which is where `isRoot()` would.
     */
    #[Test]
    public function rootlineCrossesATypeBoundary(): void
    {
        $rootline = $this->subject()->getCategoryRootline(7);

        $this->assertSame(['Second Type Root', 'Child Of Another Type'], array_column($rootline, 'title'));
    }

    #[Test]
    public function rootlineOfAnUnknownCategoryIsEmpty(): void
    {
        $this->assertSame([], $this->subject()->getCategoryRootline(999));
    }

    /**
     * Reached from `Category::getParentId()` of a root category, which is `0`.
     */
    #[Test]
    public function rootlineOfUidZeroIsEmpty(): void
    {
        $this->assertSame([], $this->subject()->getCategoryRootline(0));
    }

    /**
     * `getCategoryArray()` lifts every restriction except the deleted one, so a deleted
     * category is as unresolvable as an unknown one.
     */
    #[Test]
    public function rootlineOfADeletedCategoryIsEmpty(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/CategoryRepository/categoryTreeBroken.csv');

        $this->assertSame([], $this->subject()->getCategoryRootline(8));
    }

    /**
     * Deleting a category leaves its children in place, so an ancestor going missing is
     * ordinary editorial data. What could be resolved is returned rather than nothing.
     */
    #[Test]
    public function rootlineStopsWhereAnAncestorWasDeleted(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/CategoryRepository/categoryTreeBroken.csv');

        $rootline = $this->subject()->getCategoryRootline(9);

        $this->assertSame(['Child Of Deleted Root'], array_column($rootline, 'title'));
    }

    #[Test]
    public function rootlineOfASelfReferencingCategoryHoldsThatCategoryOnly(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/CategoryRepository/categoryTreeBroken.csv');

        $rootline = $this->subject()->getCategoryRootline(10);

        $this->assertSame(['Self Referencing Category'], array_column($rootline, 'title'));
    }

    /**
     * Two categories pointing at each other: the walk ends when it reaches a uid it has
     * already collected, so the entry category is the last element of the reversed result.
     */
    #[Test]
    public function rootlineOfACycleEndsWhereItStarted(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/CategoryRepository/categoryTreeBroken.csv');

        $rootline = $this->subject()->getCategoryRootline(11);

        $this->assertSame(['Cycle Second', 'Cycle First'], array_column($rootline, 'title'));
    }

    private function subject(): CategoryRepository
    {
        return $this->get(CategoryRepository::class);
    }
}
