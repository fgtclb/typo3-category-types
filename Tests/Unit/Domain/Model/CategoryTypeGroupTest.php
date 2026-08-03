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
            ['identifier' => 'partners', 'group' => 'academic', 'priority' => 5],
            $subject->toArray(),
        );
    }

    #[Test]
    public function arrayRepresentationCarriesEveryProperty(): void
    {
        $subject = new CategoryTypeGroup('programs', 'academic', 20);

        $this->assertSame(
            ['identifier' => 'programs', 'group' => 'academic', 'priority' => 20],
            $subject->toArray(),
        );
    }

    #[Test]
    public function fromArrayBuildsANewGroup(): void
    {
        $built = CategoryTypeGroup::fromArray(['identifier' => 'built', 'group' => 'other', 'priority' => 9]);

        $this->assertSame(['identifier' => 'built', 'group' => 'other', 'priority' => 9], $built->toArray());
    }

    #[Test]
    public function fromArrayDefaultsMissingKeysAndCastsScalars(): void
    {
        $built = CategoryTypeGroup::fromArray(['priority' => '7']);

        $this->assertSame(['identifier' => '', 'group' => '', 'priority' => 7], $built->toArray());
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

        $this->assertSame(['identifier' => 'built', 'group' => 'other', 'priority' => 9], $built->toArray());
        $this->assertSame(['identifier' => 'original', 'group' => 'academic', 'priority' => 1], $subject->toArray());
    }
}
