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
