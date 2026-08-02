<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Unit\Collection;

use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Domain\Model\Category;
use FGTCLB\CategoryTypes\Domain\Model\CategoryType;
use FGTCLB\CategoryTypes\Exception\CategoryExistException;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The collection groups categories by their type identifier, and the identifiers it
 * accepts are the ones `setTypeIdentifiers()` was given — normally from
 * `CategoryCollectionFactory`, which takes them from the registry group.
 *
 * The categories carry their type as a plain string here: `Category::getType()` resolves
 * through the registry and returns `null` for an unregistered type, while the collection
 * groups by `(string)$category->getType()`, so an untyped category groups under `''`.
 */
final class CategoryCollectionTest extends UnitTestCase
{
    private function category(int $uid, string $title = 'Category'): Category
    {
        return new Category(uid: $uid, parentId: 0, title: $title);
    }

    /**
     * Builds a category that carries the given type. `Category` resolves its type in the
     * constructor through `GeneralUtility::makeInstance(CategoryTypeRegistry::class)`, so
     * a matching registry is queued for exactly that call — see `CategoryTest` for why
     * this is `addInstance()` rather than a singleton.
     */
    private function typedCategory(int $uid, string $typeIdentifier, string $group = 'testing'): Category
    {
        $registry = new CategoryTypeRegistry();
        $registry->attach(new CategoryType(
            identifier: $typeIdentifier,
            extensionKey: 'test_extension',
            title: ucfirst($typeIdentifier),
            group: $group,
            icon: '',
            priority: 0,
        ));
        GeneralUtility::addInstance(CategoryTypeRegistry::class, $registry);

        return new Category(
            uid: $uid,
            parentId: 0,
            title: 'Category ' . $uid,
            type: $typeIdentifier,
            typeGroup: $group,
        );
    }

    #[Test]
    public function freshCollectionIsEmpty(): void
    {
        $subject = new CategoryCollection();

        $this->assertCount(0, $subject);
        $this->assertSame([], $subject->getAllCategoriesByType());
        $this->assertSame(CategoryCollection::class, (string)$subject);
    }

    #[Test]
    public function attachedCategoriesAreCounted(): void
    {
        $subject = new CategoryCollection();
        $subject->attach($this->category(1));
        $subject->attach($this->category(2));

        $this->assertCount(2, $subject);
    }

    #[Test]
    public function attachingTheSameUidTwiceIsRejected(): void
    {
        $subject = new CategoryCollection();
        $subject->attach($this->category(1));

        $this->expectException(CategoryExistException::class);
        $this->expectExceptionCode(1739368562);

        $subject->attach($this->category(1, 'Another title'));
    }

    #[Test]
    public function attachedCategoryIsReportedAsExisting(): void
    {
        $category = $this->category(1);

        $subject = new CategoryCollection();
        $subject->attach($category);

        $this->assertTrue($subject->exist($category));
        $this->assertFalse($subject->exist($this->category(2)));
    }

    #[Test]
    public function collectionIteratesOverItsCategoriesKeyedByUid(): void
    {
        $first = $this->category(11);
        $second = $this->category(22);

        $subject = new CategoryCollection();
        $subject->attach($first);
        $subject->attach($second);

        $seen = [];
        foreach ($subject as $key => $category) {
            $seen[$key] = $category;
        }

        $this->assertSame([11 => $first, 22 => $second], $seen);
    }

    #[Test]
    public function iterationCanBeRepeatedAfterRewind(): void
    {
        $subject = new CategoryCollection();
        $subject->attach($this->category(1));

        $this->assertCount(1, iterator_to_array($subject));
        $this->assertCount(1, iterator_to_array($subject));
    }

    /**
     * Without registered type identifiers there is nothing to group by, so the grouped
     * view stays empty even though the collection holds categories.
     */
    #[Test]
    public function groupingIsEmptyWhileNoTypeIdentifiersAreKnown(): void
    {
        $subject = new CategoryCollection();
        $subject->attach($this->typedCategory(1, 'research_field'));

        $this->assertSame([], $subject->getAllCategoriesByType());
    }

    #[Test]
    public function everyKnownTypeGetsAnEntryEvenWithoutCategories(): void
    {
        $subject = new CategoryCollection();
        $subject->setTypeIdentifiers(['research_field', 'country']);

        $this->assertSame(['research_field' => [], 'country' => []], $subject->getAllCategoriesByType());
    }

    #[Test]
    public function categoriesAreGroupedByTheirTypeIdentifier(): void
    {
        $field = $this->typedCategory(1, 'research_field');
        $otherField = $this->typedCategory(2, 'research_field');
        $country = $this->typedCategory(3, 'country');

        $subject = new CategoryCollection();
        $subject->setTypeIdentifiers(['research_field', 'country']);
        $subject->attach($field);
        $subject->attach($otherField);
        $subject->attach($country);

        $this->assertSame(
            [
                'research_field' => [1 => $field, 2 => $otherField],
                'country' => [3 => $country],
            ],
            $subject->getAllCategoriesByType(),
        );
    }

    /**
     * A category whose type is not part of the group is dropped from the grouped view
     * rather than added under its own key — the same constraint `CategoryRepository`
     * applies in SQL, here as a second line of defence.
     */
    #[Test]
    public function categoryOfAnUnknownTypeIsNotGrouped(): void
    {
        $subject = new CategoryCollection();
        $subject->setTypeIdentifiers(['research_field']);
        $subject->attach($this->typedCategory(1, 'country'));

        $this->assertSame(['research_field' => []], $subject->getAllCategoriesByType());
    }

    #[Test]
    public function categoriesOfATypeAreLookedUpByName(): void
    {
        $field = $this->typedCategory(1, 'research_field');

        $subject = new CategoryCollection();
        $subject->setTypeIdentifiers(['research_field']);
        $subject->attach($field);

        $this->assertSame([1 => $field], $subject->getCategoriesByTypeName('research_field'));
    }

    /**
     * The lookup normalises camel case, which is what makes `{categories.researchField}`
     * work in Fluid for a `research_field` type.
     */
    #[Test]
    public function typeNameLookupAcceptsCamelCase(): void
    {
        $field = $this->typedCategory(1, 'research_field');

        $subject = new CategoryCollection();
        $subject->setTypeIdentifiers(['research_field']);
        $subject->attach($field);

        $this->assertSame([1 => $field], $subject->getCategoriesByTypeName('researchField'));
        $this->assertSame([1 => $field], $subject->offsetGet('researchField'));
        // The magic accessor is what makes `{categories.researchField}` work in a
        // template that calls it as a method rather than as an offset.
        $this->assertSame([1 => $field], $subject->__call('researchField', []));
    }

    #[Test]
    public function unknownTypeNameIsRejected(): void
    {
        $subject = new CategoryCollection();
        $subject->setTypeIdentifiers(['research_field']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1739372162);

        $subject->getCategoriesByTypeName('country');
    }

    /**
     * `@implements \ArrayAccess<string, Category[]>` declares string offsets, but PHP
     * hands whatever the caller wrote to the methods, so both guard against it. The
     * offset is produced as `mixed` here to exercise that guard rather than the
     * annotation.
     */
    #[Test]
    public function nonStringOffsetsAreRejectedByArrayAccess(): void
    {
        $subject = new CategoryCollection();
        $subject->setTypeIdentifiers(['research_field']);
        $offset = $this->numericOffset();

        $this->assertFalse($subject->offsetExists($offset));
        $this->assertFalse($subject->offsetGet($offset));
    }

    private function numericOffset(): mixed
    {
        return 1;
    }

    #[Test]
    public function writingThroughArrayAccessIsRejected(): void
    {
        $subject = new CategoryCollection();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1683214236549);

        $subject->offsetSet('research_field', []);
    }

    #[Test]
    public function unsettingThroughArrayAccessIsRejected(): void
    {
        $subject = new CategoryCollection();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1683214246022);

        $subject->offsetUnset('research_field');
    }
}
