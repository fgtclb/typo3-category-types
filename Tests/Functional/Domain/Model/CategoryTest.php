<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\Domain\Model;

use FGTCLB\CategoryTypes\Domain\Model\Category;
use FGTCLB\CategoryTypes\Tests\Functional\AbstractCategoryTypesTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * The two methods of `Category` that reach the database: `getParent()` looks the parent up
 * through `CategoryRepository::findParent()`, and `isRoot()` builds on it.
 *
 * `isRoot()` decides where an option ends up in a grouped filter select
 * (`ViewHelpers\Form\FilterSelectViewHelper`), and it does not mean "has no parent": a
 * category whose parent belongs to a different type is a root as well, because the select
 * only ever shows one type at a time.
 *
 * The remaining behaviour of the model is covered without a database in
 * `Tests/Unit/Domain/Model/CategoryTest`.
 */
final class CategoryTest extends AbstractCategoryTypesTestCase
{
    protected function setUp(): void
    {
        $this->addTestExtension(...array_values([
            'tests/category-types-group',
        ]));
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/CategoryRepository/categoryTree.csv');
    }

    #[Test]
    public function categoryWithoutAParentHasNone(): void
    {
        $category = $this->category(uid: 1, parentId: 0);

        $this->assertFalse($category->hasParent());
        $this->assertNull($category->getParent());
        $this->assertTrue($category->isRoot());
    }

    #[Test]
    public function parentIsLookedUpInTheDatabase(): void
    {
        $parent = $this->category(uid: 2, parentId: 1)->getParent();

        $this->assertNotNull($parent);
        $this->assertSame('Root Category', $parent->getTitle());
    }

    #[Test]
    public function categoryBelowAParentOfTheSameTypeIsNotARoot(): void
    {
        $this->assertFalse($this->category(uid: 2, parentId: 1)->isRoot());
        $this->assertFalse($this->category(uid: 3, parentId: 2)->isRoot());
    }

    #[Test]
    public function categoryBelowAParentOfAnotherTypeIsARoot(): void
    {
        // Category 7 hangs below category 6, which carries `testing_second`.
        $this->assertTrue($this->category(uid: 7, parentId: 6)->isRoot());
    }

    /**
     * `findParent()` applies the default restrictions, so a hidden ancestor is invisible and
     * its children move up to the root level rather than disappearing from the select.
     */
    #[Test]
    public function categoryBelowAHiddenParentIsARoot(): void
    {
        $category = $this->category(uid: 5, parentId: 4);

        $this->assertNull($category->getParent());
        $this->assertTrue($category->isRoot());
    }

    /**
     * The lookup uses the group of the resolved type, so a category built without one falls
     * back to `default` - where the fixture group's types are not registered, and the parent
     * comes back untyped instead of failing.
     */
    #[Test]
    public function parentOfAnUntypedCategoryIsResolvedInTheDefaultGroup(): void
    {
        $parent = (new Category(uid: 2, parentId: 1, title: 'Child Category'))->getParent();

        $this->assertNotNull($parent);
        $this->assertNull($parent->getType());
    }

    private function category(int $uid, int $parentId, string $type = 'testing_first'): Category
    {
        return new Category(
            uid: $uid,
            parentId: $parentId,
            title: 'Category ' . $uid,
            type: $type,
            typeGroup: 'testing',
        );
    }
}
