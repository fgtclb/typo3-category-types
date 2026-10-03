<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Unit\Domain\Model;

use FGTCLB\CategoryTypes\Domain\Model\CategoryType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CategoryTypeTest extends UnitTestCase
{
    /**
     * @return array{
     *     identifier: string,
     *     extensionKey: string,
     *     title: string,
     *     group: string,
     *     icon: string,
     *     priority: int,
     *     inlineIcon: bool,
     *     frontendIcon: string,
     *     frontendInlineIcon: bool|null,
     * }
     */
    private function completeArray(): array
    {
        return [
            'identifier' => 'field_of_study',
            'extensionKey' => 'academic_programs',
            'title' => 'Field of study',
            'group' => 'programs',
            'icon' => 'EXT:academic_programs/Resources/Public/Icons/field_of_study.svg',
            'priority' => 30,
            'inlineIcon' => true,
            'frontendIcon' => 'EXT:academic_programs/Resources/Public/Icons/field_of_study_frontend.svg',
            'frontendInlineIcon' => false,
        ];
    }

    #[Test]
    public function everyConstructorArgumentIsExposedByItsGetter(): void
    {
        $values = $this->completeArray();
        $subject = new CategoryType(...$values);

        $this->assertSame($values['identifier'], $subject->getIdentifier());
        $this->assertSame($values['extensionKey'], $subject->getExtensionKey());
        $this->assertSame($values['title'], $subject->getTitle());
        $this->assertSame($values['group'], $subject->getGroup());
        $this->assertSame($values['icon'], $subject->getIcon());
        $this->assertSame($values['priority'], $subject->getPriority());
        $this->assertSame($values['inlineIcon'], $subject->isInlineIcon());
        $this->assertSame($values['frontendIcon'], $subject->getFrontendIcon());
        $this->assertSame($values['frontendInlineIcon'], $subject->isFrontendInlineIcon());
    }

    /**
     * The icon identifier is what `ServiceProvider::addIcons()` registers with the
     * `IconRegistry` and what the backend then resolves an icon by, so its shape is
     * public API rather than an implementation detail.
     */
    #[Test]
    public function iconIdentifierIsComposedOfPrefixGroupAndIdentifier(): void
    {
        $subject = new CategoryType(...$this->completeArray());

        $this->assertSame('category_types.programs.field_of_study', $subject->getIconIdentifier());
    }

    #[Test]
    public function stringRepresentationIsTheIdentifier(): void
    {
        $subject = new CategoryType(...$this->completeArray());

        $this->assertSame('field_of_study', (string)$subject);
    }

    #[Test]
    public function arrayAndJsonRepresentationCarryEveryProperty(): void
    {
        $values = $this->completeArray();
        $subject = new CategoryType(...$values);

        $this->assertSame($values, $subject->toArray());
        $this->assertSame($values, $subject->jsonSerialize());
    }

    /**
     * @return \Generator<string, array{0: callable(array<string, mixed>): CategoryType}>
     */
    public static function factoryMethods(): \Generator
    {
        yield 'fromArray' => [static fn(array $array): CategoryType => CategoryType::fromArray($array)];
        // `__set_state()` is what `var_export()` writes into the core cache, so the cached
        // registry is rebuilt through it on every request that hits the cache.
        yield '__set_state' => [static fn(array $array): CategoryType => CategoryType::__set_state($array)];
    }

    /**
     * @param callable(array<string, mixed>): CategoryType $factory
     */
    #[DataProvider('factoryMethods')]
    #[Test]
    public function factoryMethodRestoresEveryProperty(callable $factory): void
    {
        $values = $this->completeArray();

        $this->assertSame($values, $factory($values)->toArray());
    }

    /**
     * A YAML file may leave any key out — the loader only insists on `identifier` and
     * `group`. Everything else falls back rather than failing.
     *
     * @param callable(array<string, mixed>): CategoryType $factory
     */
    #[DataProvider('factoryMethods')]
    #[Test]
    public function factoryMethodDefaultsMissingKeys(callable $factory): void
    {
        $subject = $factory(['identifier' => 'minimal']);

        $this->assertSame(
            [
                'identifier' => 'minimal',
                'extensionKey' => '',
                'title' => '',
                'group' => '',
                'icon' => '',
                'priority' => 0,
                'inlineIcon' => false,
                'frontendIcon' => '',
                'frontendInlineIcon' => null,
            ],
            $subject->toArray(),
        );
    }

    /**
     * `priority` arrives as a string from YAML often enough to matter, and the cache
     * round trip has to survive it as well.
     *
     * @param callable(array<string, mixed>): CategoryType $factory
     */
    #[DataProvider('factoryMethods')]
    #[Test]
    public function factoryMethodCastsScalarsToTheDeclaredTypes(callable $factory): void
    {
        $subject = $factory(['identifier' => 'cast', 'priority' => '42']);

        $this->assertSame(42, $subject->getPriority());
    }

    /**
     * Inlining an icon file is opt in, and the default has to be the conservative one:
     * a type that says nothing keeps the core provider and its `<img>` markup. A YAML
     * file that does say something says it as a boolean, and the cached registry is
     * rebuilt through `__set_state()`, so both paths are covered.
     *
     * @param callable(array<string, mixed>): CategoryType $factory
     */
    #[DataProvider('factoryMethods')]
    #[Test]
    public function inlineIconDefaultsToOffAndIsRestoredWhenSet(callable $factory): void
    {
        $this->assertFalse($factory(['identifier' => 'silent'])->isInlineIcon());
        $this->assertTrue($factory(['identifier' => 'asking', 'inlineIcon' => true])->isInlineIcon());
        $this->assertFalse($factory(['identifier' => 'declining', 'inlineIcon' => false])->isInlineIcon());
    }

    /**
     * Each row of the spec requirement "Inlining follows the file it is declared for".
     * The bitmap row is a provider decision and is covered where the provider is chosen.
     *
     * @return \Generator<string, array{0: array<string, mixed>, 1: string, 2: bool}>
     */
    public static function frontendIconDeclarations(): \Generator
    {
        yield 'one file, no flag' => [
            ['icon' => 'Degree.svg'],
            'Degree.svg',
            false,
        ];
        yield 'one file, inlined everywhere' => [
            ['icon' => 'Degree.svg', 'inlineIcon' => true],
            'Degree.svg',
            true,
        ];
        yield 'a frontend file without its own flag' => [
            ['icon' => 'Degree.svg', 'inlineIcon' => true, 'frontendIcon' => 'DegreeFrontend.svg'],
            'DegreeFrontend.svg',
            false,
        ];
        yield 'a frontend file with its own flag' => [
            ['icon' => 'Degree.svg', 'frontendIcon' => 'DegreeFrontend.svg', 'frontendInlineIcon' => true],
            'DegreeFrontend.svg',
            true,
        ];
        yield 'the same file, inlined only in the backend' => [
            ['icon' => 'Degree.svg', 'inlineIcon' => true, 'frontendInlineIcon' => false],
            'Degree.svg',
            false,
        ];
        yield 'the same file, inlined only in the frontend' => [
            ['icon' => 'Degree.svg', 'frontendInlineIcon' => true],
            'Degree.svg',
            true,
        ];
        yield 'a frontend file only' => [
            ['frontendIcon' => 'DegreeFrontend.svg'],
            'DegreeFrontend.svg',
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
        $subject = CategoryType::fromArray(['identifier' => 'degree', 'group' => 'programs'] + $declaration);

        $this->assertSame($expectedFile, $subject->getFrontendIcon());
        $this->assertSame($expectedInline, $subject->isFrontendInlineIcon());
    }

    /**
     * The backend reads `icon` and `inlineIcon` alone, whatever the frontend pair says.
     */
    #[Test]
    public function frontendPairDoesNotChangeTheBackendIcon(): void
    {
        $subject = CategoryType::fromArray([
            'identifier' => 'degree',
            'icon' => 'Degree.svg',
            'inlineIcon' => true,
            'frontendIcon' => 'DegreeFrontend.svg',
            'frontendInlineIcon' => false,
        ]);

        $this->assertSame('Degree.svg', $subject->getIcon());
        $this->assertTrue($subject->isInlineIcon());
    }

    /**
     * A cache entry written before the frontend pair existed has neither key. Restored, it
     * shows the frontend what it showed before: the `icon` file, inlined when `inlineIcon`
     * says so.
     */
    #[Test]
    public function cacheEntryWithoutTheFrontendPairRestoresTheEarlierFrontend(): void
    {
        $inlined = CategoryType::__set_state(['identifier' => 'degree', 'icon' => 'Degree.svg', 'inlineIcon' => true]);
        $plain = CategoryType::__set_state(['identifier' => 'subject', 'icon' => 'Subject.svg', 'inlineIcon' => false]);

        $this->assertSame('Degree.svg', $inlined->getFrontendIcon());
        $this->assertTrue($inlined->isFrontendInlineIcon());
        $this->assertSame('Subject.svg', $plain->getFrontendIcon());
        $this->assertFalse($plain->isFrontendInlineIcon());
        $this->assertNull($inlined->toArray()['frontendInlineIcon']);
    }

    #[Test]
    public function exportedTypeCanBeRestoredFromItsVarExport(): void
    {
        $subject = new CategoryType(...$this->completeArray());

        /** @var CategoryType $restored */
        $restored = eval('return ' . var_export($subject, true) . ';');

        $this->assertInstanceOf(CategoryType::class, $restored);
        $this->assertSame($subject->toArray(), $restored->toArray());
    }
}
