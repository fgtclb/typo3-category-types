<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Unit\Domain\Model;

use FGTCLB\CategoryTypes\Domain\Model\CategoryTypeGroup;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $this->assertSame('', $subject->getFrontendIcon());
        $this->assertFalse($subject->isFrontendInlineIcon());
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
            ['identifier' => 'partners', 'group' => 'academic', 'priority' => 5, 'title' => '', 'icon' => '', 'inlineIcon' => false, 'frontendIcon' => '', 'frontendInlineIcon' => null],
            $subject->toArray(),
        );
    }

    #[Test]
    public function arrayRepresentationCarriesEveryProperty(): void
    {
        $subject = new CategoryTypeGroup('programs', 'academic', 20);

        $this->assertSame(
            ['identifier' => 'programs', 'group' => 'academic', 'priority' => 20, 'title' => '', 'icon' => '', 'inlineIcon' => false, 'frontendIcon' => '', 'frontendInlineIcon' => null],
            $subject->toArray(),
        );
    }

    #[Test]
    public function fromArrayBuildsANewGroup(): void
    {
        $built = CategoryTypeGroup::fromArray(['identifier' => 'built', 'group' => 'other', 'priority' => 9]);

        $this->assertSame(['identifier' => 'built', 'group' => 'other', 'priority' => 9, 'title' => '', 'icon' => '', 'inlineIcon' => false, 'frontendIcon' => '', 'frontendInlineIcon' => null], $built->toArray());
    }

    #[Test]
    public function fromArrayDefaultsMissingKeysAndCastsScalars(): void
    {
        $built = CategoryTypeGroup::fromArray(['priority' => '7']);

        $this->assertSame(['identifier' => '', 'group' => '', 'priority' => 7, 'title' => '', 'icon' => '', 'inlineIcon' => false, 'frontendIcon' => '', 'frontendInlineIcon' => null], $built->toArray());
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

        $this->assertSame(['identifier' => 'built', 'group' => 'other', 'priority' => 9, 'title' => '', 'icon' => '', 'inlineIcon' => false, 'frontendIcon' => '', 'frontendInlineIcon' => null], $built->toArray());
        $this->assertSame(['identifier' => 'original', 'group' => 'academic', 'priority' => 1, 'title' => '', 'icon' => '', 'inlineIcon' => false, 'frontendIcon' => '', 'frontendInlineIcon' => null], $subject->toArray());
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
     * The prefix differs from the `category_types.` of the type icons in its fifteenth
     * character, so a group icon identifier never equals a type icon identifier, not even
     * for a group named `group`.
     */
    #[Test]
    public function iconIdentifierHasAPrefixOfItsOwn(): void
    {
        $this->assertSame('category_types_group.programs', (new CategoryTypeGroup('programs'))->getIconIdentifier());
        $this->assertSame('category_types_group.group', (new CategoryTypeGroup('group'))->getIconIdentifier());
    }

    /**
     * The rule of the type icons, see `CategoryTypeTest::frontendIconDeclarations()`.
     *
     * @return \Generator<string, array{0: array<string, mixed>, 1: string, 2: bool}>
     */
    public static function frontendIconDeclarations(): \Generator
    {
        yield 'one file, inlined everywhere' => [
            ['icon' => 'Programs.svg', 'inlineIcon' => true],
            'Programs.svg',
            true,
        ];
        yield 'a frontend file without its own flag' => [
            ['icon' => 'Programs.svg', 'inlineIcon' => true, 'frontendIcon' => 'ProgramsFrontend.svg'],
            'ProgramsFrontend.svg',
            false,
        ];
        yield 'a frontend file with its own flag' => [
            ['icon' => 'Programs.svg', 'frontendIcon' => 'ProgramsFrontend.svg', 'frontendInlineIcon' => true],
            'ProgramsFrontend.svg',
            true,
        ];
        yield 'the same file, inlined only in the backend' => [
            ['icon' => 'Programs.svg', 'inlineIcon' => true, 'frontendInlineIcon' => false],
            'Programs.svg',
            false,
        ];
    }

    /**
     * @param array<string, mixed> $declaration
     */
    #[DataProvider('frontendIconDeclarations')]
    #[Test]
    public function frontendShowsItsOwnFileAndInlinesItOnlyWhenAskedFor(array $declaration, string $expectedFile, bool $expectedInline): void
    {
        $subject = CategoryTypeGroup::fromArray(['identifier' => 'programs'] + $declaration);

        $this->assertSame($expectedFile, $subject->getFrontendIcon());
        $this->assertSame($expectedInline, $subject->isFrontendInlineIcon());
        $this->assertSame($declaration['icon'], $subject->getIcon());
        $this->assertSame($declaration['inlineIcon'] ?? false, $subject->isInlineIcon());
    }

    #[Test]
    public function frontendPairCanBeReplacedByItsSetters(): void
    {
        $subject = new CategoryTypeGroup('programs', icon: 'Programs.svg', inlineIcon: true);
        $subject->setFrontendIcon('ProgramsFrontend.svg');
        $subject->setFrontendInlineIcon(true);

        $this->assertSame('ProgramsFrontend.svg', $subject->getFrontendIcon());
        $this->assertTrue($subject->isFrontendInlineIcon());

        $subject->setFrontendInlineIcon(null);

        $this->assertFalse($subject->isFrontendInlineIcon());
        $this->assertNull($subject->toArray()['frontendInlineIcon']);
    }

    /**
     * A cache entry written before the frontend pair existed restores the frontend it had.
     */
    #[Test]
    public function cacheEntryWithoutTheFrontendPairRestoresTheEarlierFrontend(): void
    {
        $restored = CategoryTypeGroup::__set_state(['identifier' => 'programs', 'icon' => 'Programs.svg', 'inlineIcon' => true]);

        $this->assertSame('Programs.svg', $restored->getFrontendIcon());
        $this->assertTrue($restored->isFrontendInlineIcon());
    }

    #[Test]
    public function fromArrayReadsTitleIconAndInlineIcon(): void
    {
        $built = CategoryTypeGroup::fromArray(['identifier' => 'programs', 'title' => 'Study programs', 'icon' => 'EXT:ext/Icons/Programs.svg', 'inlineIcon' => 1]);

        $this->assertSame(
            ['identifier' => 'programs', 'group' => '', 'priority' => 0, 'title' => 'Study programs', 'icon' => 'EXT:ext/Icons/Programs.svg', 'inlineIcon' => true, 'frontendIcon' => '', 'frontendInlineIcon' => null],
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
        $subject = new CategoryTypeGroup('programs', 'academic', 20, 'Study programs', 'EXT:ext/Icons/Programs.svg', true, 'EXT:ext/Icons/ProgramsFrontend.svg', false);

        $this->assertSame($subject->toArray(), CategoryTypeGroup::__set_state($subject->toArray())->toArray());
    }
}
