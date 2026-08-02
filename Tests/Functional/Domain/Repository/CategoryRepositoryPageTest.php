<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\Domain\Repository;

use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use FGTCLB\CategoryTypes\Tests\Functional\AbstractCategoryTypesTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Direct coverage for `CategoryRepository::findByGroupAndPageId()`.
 *
 * The method reads the categories a page carries in its own `categories` field, and is the
 * only consumer of the `$includeHidden` switch. Its single caller is the backend page
 * layout view helper `ViewHelpers\Be\CategoryViewHelper`, which always passes `true` —
 * hidden records have to show up in the backend module of `EXT:academic_partners`,
 * `EXT:academic_programs` and `EXT:academic_projects`.
 *
 * Both directions of that switch are asserted here, and for the page as well as for the
 * category: the query joins `pages`, so the restrictions apply to that table too, which is
 * what the comment in the method refers to.
 *
 * `EXT:category_types` registers no category group of its own, so the group and the two
 * types come from the `test_category_types_group` fixture extension.
 */
final class CategoryRepositoryPageTest extends AbstractCategoryTypesTestCase
{
    private const CATEGORISED_PAGE = 2;
    private const HIDDEN_PAGE = 3;
    private const UNCATEGORISED_PAGE = 4;

    protected function setUp(): void
    {
        $this->addTestExtension(...array_values([
            'tests/category-types-group',
        ]));
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/CategoryRepository/categorisedPages.csv');
    }

    #[Test]
    public function categoriesOfThePageAreReturned(): void
    {
        $collection = $this->subject()->findByGroupAndPageId('testing', self::CATEGORISED_PAGE);

        $this->assertSame(['First Category', 'Second Category'], $this->titles($collection));
    }

    #[Test]
    public function categoryOutsideTheRequestedGroupIsIgnored(): void
    {
        // The page also carries category 4, whose `type` belongs to no registered type of
        // the group, so the `sys_category.type IN (...)` constraint filters it out.
        $collection = $this->subject()->findByGroupAndPageId('testing', self::CATEGORISED_PAGE);

        $this->assertNotContains('Untyped Category', $this->titles($collection));
    }

    #[Test]
    public function pageWithoutCategoriesReturnsAnEmptyCollection(): void
    {
        $collection = $this->subject()->findByGroupAndPageId('testing', self::UNCATEGORISED_PAGE);

        $this->assertCount(0, $collection);
    }

    #[Test]
    public function hiddenCategoryIsOmittedByDefault(): void
    {
        $collection = $this->subject()->findByGroupAndPageId('testing', self::CATEGORISED_PAGE);

        $this->assertNotContains('Hidden Category', $this->titles($collection));
    }

    #[Test]
    public function hiddenCategoryIsReturnedWhenHiddenRecordsAreRequested(): void
    {
        $collection = $this->subject()->findByGroupAndPageId('testing', self::CATEGORISED_PAGE, true);

        $this->assertSame(
            ['First Category', 'Hidden Category', 'Second Category'],
            $this->titles($collection),
        );
    }

    /**
     * The hidden state travels with the category, so a template can tell the two apart
     * instead of only seeing more rows.
     */
    #[Test]
    public function hiddenStateIsCarriedByTheCategory(): void
    {
        $hidden = [];
        foreach ($this->subject()->findByGroupAndPageId('testing', self::CATEGORISED_PAGE, true) as $category) {
            $hidden[$category->getTitle()] = $category->getHidden();
        }
        ksort($hidden);

        $this->assertSame(
            ['First Category' => false, 'Hidden Category' => true, 'Second Category' => false],
            $hidden,
        );
    }

    /**
     * The restrictions cover the joined `pages` table as well, so a hidden page hides its
     * categories - which is exactly what the backend caller lifts.
     */
    #[Test]
    public function categoriesOfAHiddenPageAreOmittedByDefault(): void
    {
        $collection = $this->subject()->findByGroupAndPageId('testing', self::HIDDEN_PAGE);

        $this->assertCount(0, $collection);
    }

    #[Test]
    public function categoriesOfAHiddenPageAreReturnedWhenHiddenRecordsAreRequested(): void
    {
        $collection = $this->subject()->findByGroupAndPageId('testing', self::HIDDEN_PAGE, true);

        $this->assertSame(['First Category'], $this->titles($collection));
    }

    #[Test]
    public function collectionIsPreparedForTheRequestedGroup(): void
    {
        $collection = $this->subject()->findByGroupAndPageId('testing', self::UNCATEGORISED_PAGE);

        // Even without a single row the collection knows the types of the group, so a
        // template iterating them renders an empty group rather than nothing.
        $this->assertSame(
            ['testing_first' => [], 'testing_second' => []],
            $collection->getAllCategoriesByType(),
        );
    }

    #[Test]
    public function unknownGroupIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1683633304209);

        $this->subject()->findByGroupAndPageId('unknown', self::CATEGORISED_PAGE);
    }

    private function subject(): CategoryRepository
    {
        return $this->get(CategoryRepository::class);
    }

    /**
     * @return string[]
     */
    private function titles(CategoryCollection $collection): array
    {
        $titles = [];
        foreach ($collection as $category) {
            $titles[] = $category->getTitle();
        }
        sort($titles);

        return $titles;
    }
}
