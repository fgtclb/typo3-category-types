<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\Domain\Repository;

use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use FGTCLB\CategoryTypes\Tests\Functional\AbstractCategoryTypesTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * The order of the four list queries of `CategoryRepository` (ACE-491).
 *
 * `CategoryCollection` iterates in insertion order, so whatever order the queries return
 * is the order every filter select and category list renders in. Before ACE-491 none of
 * them carried an ORDER BY, and the row order belonged to the DBMS - not the same list
 * twice on PostgreSQL. They now follow the manual backend order (`sys_category.sorting`,
 * TCA ctrl `sortby`) with `uid` settling ties.
 *
 * The fixture's `sorting` values deliberately contradict creation order - `Early Category`
 * (uid 2, sorting 32) precedes `Middle` (uid 3, 64) precedes `Late` (uid 1, 96) - so every
 * assertion here fails on any DBMS as soon as an ordering is dropped.
 */
final class CategoryRepositoryOrderingTest extends AbstractCategoryTypesTestCase
{
    private const EXPECTED_ORDER = ['Early Category', 'Middle Category', 'Late Category'];

    protected function setUp(): void
    {
        $this->addTestExtension(...array_values([
            'tests/category-types-group',
        ]));
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/CategoryRepository/orderedCategories.csv');
    }

    #[Test]
    public function pageCategoriesFollowTheBackendSortingOrder(): void
    {
        $collection = $this->subject()->findByGroupAndPageId('testing', 2);

        $this->assertSame(self::EXPECTED_ORDER, $this->orderedTitles($collection));
    }

    #[Test]
    public function applicableCategoriesFollowTheBackendSortingOrder(): void
    {
        $collection = $this->subject()->findAllApplicable('testing');

        $this->assertSame(self::EXPECTED_ORDER, $this->orderedTitles($collection));
    }

    /**
     * The requested uid list does not influence the order - it is a selection, not a
     * sequence: `DemandFactory` builds it from the submitted filter, whose order carries
     * no meaning.
     */
    #[Test]
    public function categoriesOfAUidListFollowTheBackendSortingOrder(): void
    {
        $collection = $this->subject()->findByGroupAndUidList('testing', [1, 2, 3]);

        $this->assertSame(self::EXPECTED_ORDER, $this->orderedTitles($collection));
    }

    #[Test]
    public function categoriesOfAContentElementFollowTheBackendSortingOrder(): void
    {
        $collection = $this->subject()->getByDatabaseFields('testing', 1);

        $this->assertSame(self::EXPECTED_ORDER, $this->orderedTitles($collection));
    }

    private function subject(): CategoryRepository
    {
        return $this->get(CategoryRepository::class);
    }

    /**
     * Unlike the `titles()` helper of the sibling tests this one preserves the collection
     * order - the order is what this class is about.
     *
     * @return string[]
     */
    private function orderedTitles(CategoryCollection $collection): array
    {
        $titles = [];
        foreach ($collection as $category) {
            $titles[] = $category->getTitle();
        }

        return $titles;
    }
}
