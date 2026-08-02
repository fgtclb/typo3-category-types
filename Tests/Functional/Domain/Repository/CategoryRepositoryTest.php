<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\Domain\Repository;

use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use FGTCLB\CategoryTypes\Tests\Functional\AbstractCategoryTypesTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Direct coverage for `CategoryRepository::getByDatabaseFields()`.
 *
 * The method used to return early when `Result::rowCount()` reported zero. For a SELECT
 * that value is driver dependent: SQLite answers `0` for a result that does carry rows,
 * so the category filter of every consuming plugin silently found nothing there (ACE-314).
 *
 * Until now the fix was guarded only from `EXT:academic_programs`, through a plugin, a
 * FlexForm, a demand factory and a frontend request - and only on `main`. The method has
 * three consumers (`academic_programs`, `academic_partners`, `academic_projects`), so a
 * regression would have been reported by the wrong extension's suite, or by none. These
 * tests call it directly instead, which also puts it on the DBMS matrix where the defect
 * actually lives.
 *
 * `EXT:category_types` registers no category group of its own, so the group and the two
 * types come from the `test_category_types_group` fixture extension.
 */
final class CategoryRepositoryTest extends AbstractCategoryTypesTestCase
{
    protected function setUp(): void
    {
        $this->addTestExtension(...array_values([
            'tests/category-types-group',
        ]));
        parent::setUp();
    }

    #[Test]
    public function categorisedContentElementReturnsItsCategories(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/CategoryRepository/categorisedContentElements.csv');

        $collection = $this->get(CategoryRepository::class)->getByDatabaseFields('testing', 1);

        // The assertion that fails on SQLite without the fix: the rows are there, only
        // the row count lied about them.
        $this->assertCount(2, $collection);

        $titles = [];
        foreach ($collection as $category) {
            $titles[] = $category->getTitle();
        }
        sort($titles);
        $this->assertSame(['First Category', 'Second Category'], $titles);
    }

    #[Test]
    public function contentElementWithoutCategoriesReturnsAnEmptyCollection(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/CategoryRepository/categorisedContentElements.csv');

        // Guards the other direction, so the removed early return cannot come back as an
        // optimisation of the no-rows path.
        $this->assertCount(0, $this->get(CategoryRepository::class)->getByDatabaseFields('testing', 2));
    }

    #[Test]
    public function categoriesOutsideTheRequestedGroupAreIgnored(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/CategoryRepository/categorisedContentElements.csv');

        // Content element 3 carries a category whose `type` belongs to no registered
        // type of the group, so the `sys_category.type IN (...)` constraint filters it.
        $this->assertCount(0, $this->get(CategoryRepository::class)->getByDatabaseFields('testing', 3));
    }
}
