<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Unit\Domain\Model;

use FGTCLB\CategoryTypes\Domain\Model\CategoryTypeGroup;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CategoryTypeGroupTest extends UnitTestCase
{
    #[Test]
    public function everyPropertyDefaultsToAnEmptyValue(): void
    {
        $subject = new CategoryTypeGroup();

        $this->assertSame('', $subject->getIdentifier());
        $this->assertSame('', $subject->getGroup());
        $this->assertSame(0, $subject->getPriority());
        $this->assertSame('', $subject->getTitle());
        $this->assertSame('', $subject->getIcon());
        $this->assertFalse($subject->isInlineIcon());
    }

    #[Test]
    public function everyConstructorArgumentIsExposedByItsGetter(): void
    {
        $subject = new CategoryTypeGroup('programs', 'academic', 20);

        $this->assertSame('programs', $subject->getIdentifier());
        $this->assertSame('academic', $subject->getGroup());
        $this->assertSame(20, $subject->getPriority());
    }

    #[Test]
    public function everyPropertyCanBeReplacedByItsSetter(): void
    {
        $subject = new CategoryTypeGroup();
        $subject->setIdentifier('partners');
        $subject->setGroup('academic');
        $subject->setPriority(5);

        $this->assertSame(
            ['identifier' => 'partners', 'group' => 'academic', 'priority' => 5, 'title' => '', 'icon' => '', 'inlineIcon' => false],
            $subject->toArray(),
        );
    }

    #[Test]
    public function arrayRepresentationCarriesEveryProperty(): void
    {
        $subject = new CategoryTypeGroup('programs', 'academic', 20);

        $this->assertSame(
            ['identifier' => 'programs', 'group' => 'academic', 'priority' => 20, 'title' => '', 'icon' => '', 'inlineIcon' => false],
            $subject->toArray(),
        );
    }

    #[Test]
    public function fromArrayBuildsANewGroup(): void
    {
        $built = CategoryTypeGroup::fromArray(['identifier' => 'built', 'group' => 'other', 'priority' => 9]);

        $this->assertSame(['identifier' => 'built', 'group' => 'other', 'priority' => 9, 'title' => '', 'icon' => '', 'inlineIcon' => false], $built->toArray());
    }

    #[Test]
    public function fromArrayDefaultsMissingKeysAndCastsScalars(): void
    {
        $built = CategoryTypeGroup::fromArray(['priority' => '7']);

        $this->assertSame(['identifier' => '', 'group' => '', 'priority' => 7, 'title' => '', 'icon' => '', 'inlineIcon' => false], $built->toArray());
    }

    /**
     * The method was an instance method until it was aligned with the static counterpart
     * on `CategoryType`. PHP allows an arrow call to a static method, so the previous
     * calling convention keeps working and the receiver is still left untouched.
     */
    #[Test]
    public function fromArrayCanStillBeCalledOnAnInstance(): void
    {
        $subject = new CategoryTypeGroup('original', 'academic', 1);

        $built = $subject->fromArray(['identifier' => 'built', 'group' => 'other', 'priority' => 9]);

        $this->assertSame(['identifier' => 'built', 'group' => 'other', 'priority' => 9, 'title' => '', 'icon' => '', 'inlineIcon' => false], $built->toArray());
        $this->assertSame(['identifier' => 'original', 'group' => 'academic', 'priority' => 1, 'title' => '', 'icon' => '', 'inlineIcon' => false], $subject->toArray());
    }

    #[Test]
    public function titleIconAndInlineIconAreCarriedOver(): void
    {
        $subject = new CategoryTypeGroup('programs', title: 'Study programs', icon: 'EXT:ext/Icons/Programs.svg', inlineIcon: true);

        $this->assertSame('Study programs', $subject->getTitle());
        $this->assertSame('EXT:ext/Icons/Programs.svg', $subject->getIcon());
        $this->assertTrue($subject->isInlineIcon());
    }

    /**
     * The group identifier makes up the icon identifier after a fixed `group` segment, next
     * to the `category_types.<group>.<type>` identifiers of the type icons.
     */
    #[Test]
    public function iconIdentifierIsNamespacedByGroup(): void
    {
        $this->assertSame('category_types.group.programs', (new CategoryTypeGroup('programs'))->getIconIdentifier());
    }

    #[Test]
    public function fromArrayReadsTitleIconAndInlineIcon(): void
    {
        $built = CategoryTypeGroup::fromArray(['identifier' => 'programs', 'title' => 'Study programs', 'icon' => 'EXT:ext/Icons/Programs.svg', 'inlineIcon' => 1]);

        $this->assertSame(
            ['identifier' => 'programs', 'group' => '', 'priority' => 0, 'title' => 'Study programs', 'icon' => 'EXT:ext/Icons/Programs.svg', 'inlineIcon' => true],
            $built->toArray(),
        );
    }

    /**
     * The loader caches the groups as exported PHP, which restores every group through
     * `__set_state()` with the properties by name. The round trip through the real cache
     * is covered by the functional loader test.
     */
    #[Test]
    public function setStateRestoresEveryProperty(): void
    {
        $subject = new CategoryTypeGroup('programs', 'academic', 20, 'Study programs', 'EXT:ext/Icons/Programs.svg', true);

        $this->assertSame($subject->toArray(), CategoryTypeGroup::__set_state($subject->toArray())->toArray());
    }
}
