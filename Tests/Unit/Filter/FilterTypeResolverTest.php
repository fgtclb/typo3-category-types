<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Unit\Filter;

use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Domain\Model\Category;
use FGTCLB\CategoryTypes\Domain\Model\CategoryType;
use FGTCLB\CategoryTypes\Filter\FilterTypeResolver;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The collection is shaped like the one a list plugin gets from
 * `CategoryRepository::findAllApplicable()`: every type of the group in registry order, and
 * categories for three of them. `costs` is a type of the group without any category.
 */
final class FilterTypeResolverTest extends UnitTestCase
{
    private function categories(): CategoryCollection
    {
        $collection = new CategoryCollection();
        $collection->setTypeIdentifiers(['degree', 'costs', 'location', 'program_type']);
        $collection->attach($this->typedCategory(1, 'degree'));
        $collection->attach($this->typedCategory(2, 'location'));
        $collection->attach($this->typedCategory(3, 'program_type'));
        $collection->attach($this->typedCategory(4, 'degree'));

        return $collection;
    }

    /**
     * `Category` resolves its type in the constructor through
     * `GeneralUtility::makeInstance(CategoryTypeRegistry::class)`, so a matching registry is
     * queued for exactly that call - see `CategoryCollectionTest`.
     */
    private function typedCategory(int $uid, string $typeIdentifier): Category
    {
        $registry = new CategoryTypeRegistry();
        $registry->attach(new CategoryType(
            identifier: $typeIdentifier,
            extensionKey: 'test_extension',
            title: ucfirst($typeIdentifier),
            group: 'programs',
            icon: '',
            priority: 0,
        ));
        GeneralUtility::addInstance(CategoryTypeRegistry::class, $registry);

        return new Category(
            uid: $uid,
            parentId: 0,
            title: 'Category ' . $uid,
            type: $typeIdentifier,
            typeGroup: 'programs',
        );
    }

    /**
     * @return \Generator<string, array{0: string, 1: list<string>}>
     */
    public static function categoryTypesDataProvider(): \Generator
    {
        yield 'empty: every type with categories, in registry order' => ['', ['degree', 'location', 'program_type']];
        yield 'only blanks and commas count as empty' => [' , ,', ['degree', 'location', 'program_type']];
        yield 'the configured order' => ['program_type,degree', ['program_type', 'degree']];
        yield 'blanks around an identifier' => [' location , degree ', ['location', 'degree']];
        yield 'a type without categories is left out' => ['costs,degree', ['degree']];
        yield 'an unregistered type is left out' => ['internship,location', ['location']];
        yield 'nothing left to offer' => ['costs,internship', []];
        yield 'a repeated type is offered once' => ['degree,location,degree', ['degree', 'location']];
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('categoryTypesDataProvider')]
    #[Test]
    public function resolvesTheOfferedTypes(string $categoryTypes, array $expected): void
    {
        $filterTypes = (new FilterTypeResolver())->resolve($this->categories(), $categoryTypes);

        $this->assertSame($expected, $filterTypes->getVisible());
        $this->assertSame([], $filterTypes->getMore());
    }

    /**
     * The split counts the filters that render: the type without categories in between takes
     * no place among the visible ones.
     */
    #[Test]
    public function aVisibleCountSplitsTheOfferedTypes(): void
    {
        $filterTypes = (new FilterTypeResolver())->resolve($this->categories(), 'location,costs,degree,program_type', 2);

        $this->assertSame(['location', 'degree'], $filterTypes->getVisible());
        $this->assertSame(['program_type'], $filterTypes->getMore());
    }

    /**
     * @return \Generator<string, array{0: int}>
     */
    public static function visibleCountShowingAllDataProvider(): \Generator
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
        yield 'as many as offered' => [3];
        yield 'more than offered' => [9];
    }

    #[DataProvider('visibleCountShowingAllDataProvider')]
    #[Test]
    public function aVisibleCountThatSplitsNothingKeepsEveryTypeVisible(int $visibleCount): void
    {
        $filterTypes = (new FilterTypeResolver())->resolve($this->categories(), '', $visibleCount);

        $this->assertSame(['degree', 'location', 'program_type'], $filterTypes->getVisible());
        $this->assertSame([], $filterTypes->getMore());
    }

    #[Test]
    public function aCollectionWithoutTypesOffersNothing(): void
    {
        $filterTypes = (new FilterTypeResolver())->resolve(new CategoryCollection(), 'degree');

        $this->assertSame([], $filterTypes->getVisible());
        $this->assertSame([], $filterTypes->getMore());
    }
}
