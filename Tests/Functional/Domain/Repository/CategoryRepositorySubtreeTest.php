<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\Domain\Repository;

use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Collection\GetCategoryCollectionInterface;
use FGTCLB\CategoryTypes\Domain\Model\Category;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use FGTCLB\CategoryTypes\Tests\Functional\AbstractCategoryTypesTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;

/**
 * The two lookups that read the category tree downwards for a list filter that includes
 * subcategories: `CategoryRepository::findDescendantUids()`, which widens a selected
 * category by its subtree, and `findAllApplicableWithSubcategories()`, which offers a
 * parent as soon as a record carries one of its subcategories.
 *
 * Both have to see the same tree: visible categories of the types of one group, in the
 * default language. The fixture tree below "Bachelor" (uid 1) therefore carries one
 * category of every kind that must end the walk: a hidden one (4), a deleted one (8), a
 * translation (9) and one of a type outside the group (10), each with a child where a
 * child can exist. 13 and 14 name each other as parent. The translation of 3 (15) names
 * Master as its parent, which an editor can do in a translation.
 *
 * `EXT:category_types` registers no category group of its own, so the group and the two
 * types come from the `test_category_types_group` fixture extension.
 */
final class CategoryRepositorySubtreeTest extends AbstractCategoryTypesTestCase
{
    protected function setUp(): void
    {
        $this->addTestExtension(...array_values([
            'tests/category-types-group',
        ]));
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/CategoryRepository/categorySubtree.csv');
    }

    /**
     * The walk reaches the grandchild (3) through the child (6) on the second level, so a
     * result in walk order would read `6, 7, 3`.
     */
    #[Test]
    public function descendantsAreTheVisibleSubtreeOfTheGroupOrderedByUid(): void
    {
        $this->assertSame([1 => [3, 6, 7]], $this->subject()->findDescendantUids('testing', 1));
    }

    #[Test]
    public function descendantsAreMappedToEveryRequestedCategory(): void
    {
        $this->assertSame(
            [2 => [12], 1 => [3, 6, 7], 6 => [3]],
            $this->subject()->findDescendantUids('testing', 2, 1, 6),
        );
    }

    #[Test]
    public function categoryWithoutSubcategoriesHasNoDescendants(): void
    {
        $this->assertSame([3 => [], 999 => []], $this->subject()->findDescendantUids('testing', 3, 999));
    }

    #[Test]
    public function noRequestedCategoryHasNoDescendants(): void
    {
        $this->assertSame([], $this->subject()->findDescendantUids('testing'));
    }

    /**
     * The hidden category ends the walk, so its child is no descendant of "Bachelor" either,
     * although it is visible itself.
     */
    #[Test]
    public function hiddenCategoryEndsTheWalk(): void
    {
        $descendants = $this->subject()->findDescendantUids('testing', 1)[1];

        $this->assertNotContains(4, $descendants);
        $this->assertNotContains(5, $descendants);
    }

    /**
     * The given categories are taken as they are: a caller hands over categories it resolved
     * for the frontend already, so the method does not check them a second time.
     */
    #[Test]
    public function givenCategoryIsNotCheckedItself(): void
    {
        $this->assertSame([4 => [5]], $this->subject()->findDescendantUids('testing', 4));
    }

    #[Test]
    public function categoryOfATypeOutsideTheGroupEndsTheWalk(): void
    {
        $descendants = $this->subject()->findDescendantUids('testing', 1)[1];

        $this->assertNotContains(10, $descendants);
        $this->assertNotContains(11, $descendants);
    }

    #[Test]
    public function deletedAndTranslatedCategoriesAreNoDescendants(): void
    {
        $descendants = $this->subject()->findDescendantUids('testing', 1)[1];

        $this->assertNotContains(8, $descendants);
        $this->assertNotContains(9, $descendants);
    }

    #[Test]
    public function categoriesReferencingEachOtherAreDescendantsOfEachOther(): void
    {
        $this->assertSame(
            [13 => [14], 14 => [13]],
            $this->subject()->findDescendantUids('testing', 13, 14),
        );
    }

    /**
     * A uid that is no category would otherwise select every root category of the group as
     * its children, `parent` being 0 for all of them.
     */
    #[Test]
    public function uidZeroHasNoDescendants(): void
    {
        $this->assertSame([0 => [], -1 => []], $this->subject()->findDescendantUids('testing', 0, -1));
    }

    #[Test]
    public function descendantsOfAnUnknownGroupAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1683633304209);

        $this->subject()->findDescendantUids('unknown', 1);
    }

    #[Test]
    public function carriedSubcategoryMakesEveryAncestorApplicable(): void
    {
        $collection = $this->subject()->findAllApplicableWithSubcategories('testing', $this->entityWithCategories(3));

        $this->assertSame(
            [
                1 => false,
                2 => true,
                3 => false,
                5 => true,
                6 => false,
                7 => true,
                11 => true,
                12 => true,
                13 => true,
                14 => true,
            ],
            $this->disabledStates($collection),
        );
    }

    /**
     * The walk upwards ends where the walk downwards ends: "Bachelor" is not offered for a
     * category below a hidden one, nor below one of a type outside the group.
     */
    #[Test]
    public function carriedCategoryBehindAHiddenOrForeignOneLeavesTheParentDisabled(): void
    {
        $states = $this->disabledStates(
            $this->subject()->findAllApplicableWithSubcategories('testing', $this->entityWithCategories(5, 11)),
        );

        $this->assertFalse($states[5]);
        $this->assertFalse($states[11]);
        $this->assertTrue($states[1]);
    }

    #[Test]
    public function carriedCategoryInALoopEnablesTheOtherOne(): void
    {
        $states = $this->disabledStates(
            $this->subject()->findAllApplicableWithSubcategories('testing', $this->entityWithCategories(13)),
        );

        $this->assertFalse($states[13]);
        $this->assertFalse($states[14]);
    }

    /**
     * The collection is overlaid with the translation, whose parent is Master. The tree is
     * still the one of the default language, the one `findDescendantUids()` reads, so a
     * program carrying 3 offers Bachelor and not Master in German as in English.
     */
    #[Test]
    public function aTranslatedFrontendWalksTheTreeOfTheDefaultLanguage(): void
    {
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));

        $collection = $this->subject()->findAllApplicableWithSubcategories('testing', $this->entityWithCategories(3));

        $titles = [];
        foreach ($collection as $category) {
            $titles[$category->getUid()] = $category->getTitle();
        }
        $this->assertSame('Bachelor of Science with Honours (translated, below Master)', $titles[3]);
        $states = $this->disabledStates($collection);
        $this->assertFalse($states[1]);
        $this->assertFalse($states[6]);
        $this->assertTrue($states[2]);
    }

    #[Test]
    public function applicableCategoriesWithoutSubcategoriesStayAsTheyWere(): void
    {
        $states = $this->disabledStates(
            $this->subject()->findAllApplicable('testing', $this->entityWithCategories(3)),
        );

        $this->assertSame([3], array_keys(array_filter($states, static fn(bool $disabled): bool => !$disabled)));
    }

    private function subject(): CategoryRepository
    {
        return $this->get(CategoryRepository::class);
    }

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
     * @return array<int, bool>
     */
    private function disabledStates(CategoryCollection $collection): array
    {
        $states = [];
        foreach ($collection as $category) {
            $states[$category->getUid()] = $category->isDisabled();
        }
        ksort($states);

        return $states;
    }
}
