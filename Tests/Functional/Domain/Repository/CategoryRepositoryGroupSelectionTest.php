<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\Domain\Repository;

use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Collection\GetCategoryCollectionInterface;
use FGTCLB\CategoryTypes\Domain\Model\Category;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use FGTCLB\CategoryTypes\Tests\Functional\AbstractCategoryTypesTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Direct coverage for the two methods selecting from a whole category group:
 * `CategoryRepository::findAllApplicable()` and `findByGroupAndUidList()`.
 *
 * `findAllApplicable()` builds the filter offered on a list view: it returns every category
 * of the group and marks the ones no listed record carries as disabled, so the select box
 * keeps its shape while unusable options grey out. Its callers are the list actions of
 * `EXT:academic_partners`, `EXT:academic_programs` and `EXT:academic_projects`.
 *
 * `findByGroupAndUidList()` resolves the submitted filter back into categories, called from
 * the `DemandFactory` of the same three extensions.
 *
 * `EXT:category_types` registers no category group of its own, so the group and the two
 * types come from the `test_category_types_group` fixture extension.
 */
final class CategoryRepositoryGroupSelectionTest extends AbstractCategoryTypesTestCase
{
    protected function setUp(): void
    {
        $this->addTestExtension(...array_values([
            'tests/category-types-group',
        ]));
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/CategoryRepository/groupedCategories.csv');
    }

    #[Test]
    public function everyCategoryOfTheGroupIsApplicable(): void
    {
        $collection = $this->subject()->findAllApplicable('testing', $this->entityWithCategories(1));

        // Category 4 carries no registered type and category 5 is hidden, so neither is part
        // of the group - the disabled marker is not what keeps them out.
        $this->assertSame(['First Category', 'Second Category', 'Third Category'], $this->titles($collection));
    }

    #[Test]
    public function categoriesNoEntityCarriesAreDisabled(): void
    {
        $collection = $this->subject()->findAllApplicable('testing', $this->entityWithCategories(1, 3));

        $this->assertSame(
            ['First Category' => false, 'Second Category' => true, 'Third Category' => false],
            $this->disabledStates($collection),
        );
    }

    /**
     * The entity list is variadic, and an empty one is what a list view without a single
     * record hands over. Everything greys out, nothing disappears.
     */
    #[Test]
    public function withoutAnyEntityEveryCategoryIsDisabled(): void
    {
        $collection = $this->subject()->findAllApplicable('testing');

        $this->assertSame(
            ['First Category' => true, 'Second Category' => true, 'Third Category' => true],
            $this->disabledStates($collection),
        );
    }

    #[Test]
    public function categoriesOfAllEntitiesAreCombined(): void
    {
        $collection = $this->subject()->findAllApplicable(
            'testing',
            $this->entityWithCategories(1),
            $this->entityWithCategories(2),
        );

        $this->assertSame(
            ['First Category' => false, 'Second Category' => false, 'Third Category' => true],
            $this->disabledStates($collection),
        );
    }

    /**
     * A category an entity carries but the group does not contain must not sneak into the
     * result through the entity - the group defines the offered filter, the entities only
     * decide which of its entries are usable.
     */
    #[Test]
    public function categoryOutsideTheGroupStaysOutEvenWhenAnEntityCarriesIt(): void
    {
        $collection = $this->subject()->findAllApplicable('testing', $this->entityWithCategories(4, 5));

        $this->assertSame(['First Category', 'Second Category', 'Third Category'], $this->titles($collection));
    }

    #[Test]
    public function applicableCategoriesOfAnUnknownGroupAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1683633304209);

        $this->subject()->findAllApplicable('unknown');
    }

    #[Test]
    public function onlyTheRequestedCategoriesAreReturned(): void
    {
        $collection = $this->subject()->findByGroupAndUidList('testing', [1, 3]);

        $this->assertSame(['First Category', 'Third Category'], $this->titles($collection));
    }

    #[Test]
    public function requestedCategoryOutsideTheGroupIsIgnored(): void
    {
        $collection = $this->subject()->findByGroupAndUidList('testing', [1, 4]);

        $this->assertSame(['First Category'], $this->titles($collection));
    }

    #[Test]
    public function requestedHiddenCategoryIsIgnored(): void
    {
        $collection = $this->subject()->findByGroupAndUidList('testing', [1, 5]);

        $this->assertSame(['First Category'], $this->titles($collection));
    }

    /**
     * `DemandFactory` builds the list with `GeneralUtility::intExplode()`, so a submitted
     * filter that was never chosen arrives as `[0]` rather than as an empty list.
     */
    #[Test]
    public function unknownCategoryUidReturnsAnEmptyCollection(): void
    {
        $this->assertCount(0, $this->subject()->findByGroupAndUidList('testing', [0]));
        $this->assertCount(0, $this->subject()->findByGroupAndUidList('testing', [999]));
    }

    #[Test]
    public function requestedCategoriesOfAnUnknownGroupAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1683633304209);

        $this->subject()->findByGroupAndUidList('unknown', [1]);
    }

    private function subject(): CategoryRepository
    {
        return $this->get(CategoryRepository::class);
    }

    /**
     * `findAllApplicable()` reads nothing but the uid from the categories of an entity, so
     * the type they carry does not matter here.
     */
    private function entityWithCategories(int ...$uids): GetCategoryCollectionInterface
    {
        $collection = new CategoryCollection();
        foreach ($uids as $uid) {
            $collection->attach(new Category(uid: $uid, parentId: 0, title: 'Carried category ' . $uid));
        }

        return new class ($collection) implements GetCategoryCollectionInterface {
            public function __construct(private readonly CategoryCollection $collection) {}

            public function getAttributes(): CategoryCollection
            {
                return $this->collection;
            }
        };
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

    /**
     * @return array<string, bool>
     */
    private function disabledStates(CategoryCollection $collection): array
    {
        $states = [];
        foreach ($collection as $category) {
            $states[$category->getTitle()] = $category->isDisabled();
        }
        ksort($states);

        return $states;
    }
}
