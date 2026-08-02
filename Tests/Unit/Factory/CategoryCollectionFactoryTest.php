<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Unit\Factory;

use FGTCLB\CategoryTypes\Domain\Model\CategoryType;
use FGTCLB\CategoryTypes\Factory\CategoryCollectionFactory;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CategoryCollectionFactoryTest extends UnitTestCase
{
    private function registry(string $group, string ...$typeIdentifiers): CategoryTypeRegistry
    {
        $registry = new CategoryTypeRegistry();
        foreach ($typeIdentifiers as $typeIdentifier) {
            $registry->attach(new CategoryType(
                identifier: $typeIdentifier,
                extensionKey: 'test_extension',
                title: ucfirst($typeIdentifier),
                group: $group,
                icon: '',
                priority: 0,
            ));
        }

        return $registry;
    }

    /**
     * The factory is the only place that tells a collection which types it may hold, so
     * the group of the registry decides what the collection can later be asked for.
     */
    #[Test]
    public function createdCollectionKnowsTheTypesOfItsGroup(): void
    {
        $subject = new CategoryCollectionFactory($this->registry('programs', 'research_field', 'degree'));

        $collection = $subject->createCategoryCollection('programs');

        $this->assertCount(0, $collection);
        $this->assertSame(
            ['research_field' => [], 'degree' => []],
            $collection->getAllCategoriesByType(),
        );
    }

    #[Test]
    public function everyCallCreatesItsOwnCollection(): void
    {
        $subject = new CategoryCollectionFactory($this->registry('programs', 'research_field'));

        $this->assertNotSame(
            $subject->createCategoryCollection('programs'),
            $subject->createCategoryCollection('programs'),
        );
    }

    /**
     * An unknown group is the registry's `InvalidArgumentException`, not an empty
     * collection — the caller asked for something that no extension registered.
     */
    #[Test]
    public function unknownGroupIsRejected(): void
    {
        $subject = new CategoryCollectionFactory($this->registry('programs', 'research_field'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1683633304209);

        $subject->createCategoryCollection('partners');
    }

    /**
     * The same on an installation where no extension ships category types at all. This
     * used to be a fatal `Error` about an uninitialised property instead.
     */
    #[Test]
    public function unknownGroupIsRejectedWithoutAnyRegisteredType(): void
    {
        $subject = new CategoryCollectionFactory(new CategoryTypeRegistry());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1683633304209);

        $subject->createCategoryCollection('programs');
    }
}
