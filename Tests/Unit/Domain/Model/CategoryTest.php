<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Unit\Domain\Model;

use FGTCLB\CategoryTypes\Domain\Model\Category;
use FGTCLB\CategoryTypes\Domain\Model\CategoryType;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * `Category` resolves its own type through
 * `GeneralUtility::makeInstance(CategoryTypeRegistry::class)` in the constructor. In a
 * request that registry comes from the container — `Services.yaml` registers it as a
 * public service built by `CategoryTypeLoader::load()`. There is no container here, so
 * the instance is queued with `GeneralUtility::addInstance()` instead, once per
 * `Category` that is about to resolve a type. It is not a singleton, so
 * `setSingletonInstance()` is not an option.
 */
final class CategoryTest extends UnitTestCase
{
    private function categoryType(string $identifier, string $group): CategoryType
    {
        return new CategoryType(
            identifier: $identifier,
            extensionKey: 'test_extension',
            title: ucfirst($identifier),
            group: $group,
            icon: 'EXT:test_extension/Resources/Public/Icons/' . $identifier . '.svg',
            priority: 0,
        );
    }

    /**
     * Queues one registry carrying the given types for the next `makeInstance()` call.
     */
    private function queueRegistryWith(CategoryType ...$categoryTypes): void
    {
        $registry = new CategoryTypeRegistry();
        $registry->attach(...$categoryTypes);
        GeneralUtility::addInstance(CategoryTypeRegistry::class, $registry);
    }

    #[Test]
    public function everyConstructorArgumentIsExposedByItsGetter(): void
    {
        $subject = new Category(uid: 12, parentId: 7, title: 'Solar research', hidden: true);

        $this->assertSame(12, $subject->getUid());
        $this->assertSame(7, $subject->getParentId());
        $this->assertSame('Solar research', $subject->getTitle());
        $this->assertTrue($subject->getHidden());
    }

    #[Test]
    public function stringRepresentationIsTheUid(): void
    {
        $this->assertSame('12', (string)new Category(12, 0, 'Solar research'));
    }

    /**
     * The default `default` is not looked up at all — a category without an explicit type
     * keeps a `null` type rather than resolving one, so nothing touches the registry.
     */
    #[Test]
    public function defaultTypeIsNotResolvedFromTheRegistry(): void
    {
        $this->assertNull((new Category(1, 0, 'Untyped'))->getType());
    }

    #[Test]
    public function typeIsResolvedFromTheRegistry(): void
    {
        $categoryType = $this->categoryType('research_field', 'programs');
        $this->queueRegistryWith($categoryType);

        $subject = new Category(1, 0, 'Solar research', 'research_field', 'programs');

        $this->assertSame($categoryType, $subject->getType());
    }

    /**
     * A type that no extension registered resolves to `null` instead of failing, so a
     * category record referencing a removed type stays renderable. The same holds for a
     * type that exists in a different group.
     */
    #[Test]
    public function unregisteredTypeResolvesToNull(): void
    {
        $this->queueRegistryWith($this->categoryType('research_field', 'programs'));
        $this->assertNull((new Category(1, 0, 'Solar research', 'gone', 'programs'))->getType());

        $this->queueRegistryWith($this->categoryType('research_field', 'programs'));
        $this->assertNull((new Category(1, 0, 'Solar research', 'research_field', 'partners'))->getType());
    }

    #[Test]
    public function categoryWithoutAParentReportsSo(): void
    {
        $subject = new Category(1, 0, 'Root');

        $this->assertFalse($subject->hasParent());
        $this->assertNull($subject->getParent());
    }

    #[Test]
    public function categoryWithAParentReportsSo(): void
    {
        $this->assertTrue((new Category(2, 1, 'Child'))->hasParent());
    }

    /**
     * Without a parent there is nothing to compare against, so the category is its own
     * root. This is the branch that answers without asking the repository.
     */
    #[Test]
    public function categoryWithoutAParentIsARoot(): void
    {
        $this->assertTrue((new Category(1, 0, 'Root'))->isRoot());
    }

    #[Test]
    public function childrenAreEmptyUntilTheyAreLoaded(): void
    {
        $this->assertNull((new Category(1, 0, 'Root'))->getChildren());
    }

    #[Test]
    public function disabledStateCanBeToggled(): void
    {
        $subject = new Category(1, 0, 'Root');
        $this->assertFalse($subject->isDisabled());

        $subject->setDisabled(true);
        $this->assertTrue($subject->isDisabled());

        $subject->setDisabled(false);
        $this->assertFalse($subject->isDisabled());
    }

    #[Test]
    public function disabledStateCanBeSetThroughTheConstructor(): void
    {
        $this->assertTrue((new Category(1, 0, 'Root', 'default', 'default', false, true))->isDisabled());
    }
}
