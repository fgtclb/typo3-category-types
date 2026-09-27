<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Unit\Loader;

use FGTCLB\CategoryTypes\Domain\Model\CategoryType;
use FGTCLB\CategoryTypes\Loader\CategoryTypeLoader;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Covers how `CategoryTypeLoader::loadUncached()` reads
 * `Configuration/CategoryTypes.yaml` of the active packages — the format handling the
 * class asks for coverage of in its own `@todo`.
 *
 * The packages are stubbed, so the fixtures under `Tests/Unit/Fixtures/Packages/` are
 * plain directories rather than installable extensions, and a test can put them in any
 * order. Order matters: `remove` and `useExisting` act on what earlier packages defined.
 *
 * `load()` is called here only for the order of the registry it builds, with a stubbed
 * cache. The cache round trip itself is covered by the functional counterpart, which
 * has a real cache backend.
 */
final class CategoryTypeLoaderTest extends UnitTestCase
{
    private function subject(string ...$packageNames): CategoryTypeLoader
    {
        $packages = [];
        foreach ($packageNames as $packageName) {
            $package = $this->createMock(PackageInterface::class);
            $package->method('getPackageKey')->willReturn($packageName);
            $package->method('getPackagePath')->willReturn(__DIR__ . '/../Fixtures/Packages/' . $packageName);
            $packages[] = $package;
        }

        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getActivePackages')->willReturn($packages);

        return new CategoryTypeLoader($this->createMock(PhpFrontend::class), $packageManager);
    }

    #[Test]
    public function noActivePackageYieldsNoTypes(): void
    {
        $this->assertSame([], $this->subject()->loadUncached());
    }

    #[Test]
    public function packageWithoutAConfigurationFileIsSkipped(): void
    {
        $this->assertSame([], $this->subject('no_configuration')->loadUncached());
    }

    #[Test]
    public function emptyConfigurationFileIsSkipped(): void
    {
        $this->assertSame([], $this->subject('empty_file')->loadUncached());
    }

    #[Test]
    public function configurationWithoutATypesSectionIsSkipped(): void
    {
        $this->assertSame([], $this->subject('without_types')->loadUncached());
    }

    /**
     * The key is `<group>.<identifier>`, which is what makes `remove` and `useExisting`
     * of a later package address a type of an earlier one.
     */
    #[Test]
    public function typesAreKeyedByGroupAndIdentifier(): void
    {
        $categoryTypes = $this->subject('base_types')->loadUncached();

        $this->assertSame(['programs.research_field', 'programs.degree'], array_keys($categoryTypes));
        $this->assertContainsOnlyInstancesOf(CategoryType::class, $categoryTypes);
    }

    /**
     * The extension key is not part of the YAML — the loader stamps it onto every type,
     * which is what tells an integrator where a type came from.
     */
    #[Test]
    public function everyTypeCarriesTheDefiningExtensionKey(): void
    {
        $categoryTypes = $this->subject('base_types')->loadUncached();

        $this->assertSame('base_types', $categoryTypes['programs.research_field']->getExtensionKey());
        $this->assertSame('base_types', $categoryTypes['programs.degree']->getExtensionKey());
    }

    #[Test]
    public function everyDeclaredValueIsCarriedOver(): void
    {
        $categoryTypes = $this->subject('base_types')->loadUncached();

        $this->assertSame(
            [
                'identifier' => 'research_field',
                'extensionKey' => 'base_types',
                'title' => 'Research field',
                'group' => 'programs',
                'icon' => 'EXT:base_types/Resources/Public/Icons/research_field.svg',
                'priority' => 10,
            ],
            $categoryTypes['programs.research_field']->toArray(),
        );
    }

    #[Test]
    public function omittedPriorityDefaultsToZero(): void
    {
        $categoryTypes = $this->subject('base_types')->loadUncached();

        $this->assertSame(0, $categoryTypes['programs.degree']->getPriority());
    }

    #[Test]
    public function typesOfSeveralPackagesAreCollected(): void
    {
        $categoryTypes = $this->subject('base_types', 'second_extension')->loadUncached();

        $this->assertSame(
            ['programs.research_field', 'programs.degree', 'partners.country'],
            array_keys($categoryTypes),
        );
    }

    #[Test]
    public function laterPackageCanRemoveAnEarlierType(): void
    {
        $categoryTypes = $this->subject('base_types', 'removing_extension')->loadUncached();

        $this->assertSame(['programs.research_field'], array_keys($categoryTypes));
    }

    /**
     * `remove` on a type nobody defined is not an error — the removing extension may
     * simply be installed without the one that would have defined it.
     */
    #[Test]
    public function removingAnUndefinedTypeIsAccepted(): void
    {
        $this->assertSame([], $this->subject('removing_extension')->loadUncached());
    }

    /**
     * Order decides: a `remove` before the definition removes nothing, because the type
     * is added afterwards.
     */
    #[Test]
    public function removalBeforeTheDefinitionHasNoEffect(): void
    {
        $categoryTypes = $this->subject('removing_extension', 'base_types')->loadUncached();

        $this->assertSame(['programs.research_field', 'programs.degree'], array_keys($categoryTypes));
    }

    #[Test]
    public function laterPackageCanOverrideSingleValuesOfAnEarlierType(): void
    {
        $categoryTypes = $this->subject('base_types', 'overriding_extension')->loadUncached();

        $this->assertSame(
            [
                'identifier' => 'research_field',
                // Reassigned to the overriding extension, so the origin stays traceable.
                'extensionKey' => 'overriding_extension',
                'title' => 'Subject area',
                'group' => 'programs',
                // Not restated by the override, so the original value survives.
                'icon' => 'EXT:base_types/Resources/Public/Icons/research_field.svg',
                'priority' => 10,
            ],
            $categoryTypes['programs.research_field']->toArray(),
        );
    }

    /**
     * The override a project needs most: another icon, nothing else.
     */
    #[Test]
    public function laterPackageCanOverrideOnlyTheIconOfAnEarlierType(): void
    {
        $categoryTypes = $this->subject('base_types', 'icon_override')->loadUncached();

        // The override changes the type in place, it does not move it to the end.
        $this->assertSame(['programs.research_field', 'programs.degree'], array_keys($categoryTypes));
        $this->assertSame(
            [
                'identifier' => 'research_field',
                'extensionKey' => 'icon_override',
                'title' => 'Research field',
                'group' => 'programs',
                'icon' => 'EXT:icon_override/Resources/Public/Icons/research_field.svg',
                'priority' => 10,
            ],
            $categoryTypes['programs.research_field']->toArray(),
        );
    }

    /**
     * How a project reorders the types an extension ships: an override that sets nothing
     * but the priority. The loader keeps the type where it was declared, the registry puts
     * it in front of `research_field`, whose priority is 10.
     */
    #[Test]
    public function priorityOfAnOverrideMovesTheTypeToTheFront(): void
    {
        $loader = $this->subject('base_types', 'priority_override');

        $this->assertSame(['programs.research_field', 'programs.degree'], array_keys($loader->loadUncached()));

        $registry = $loader->load();
        $this->assertSame(['degree', 'research_field'], $registry->getCategoryTypeIdentifierByGroup('programs'));
        $this->assertSame(
            [
                'identifier' => 'degree',
                'extensionKey' => 'priority_override',
                'title' => 'Degree',
                'group' => 'programs',
                'icon' => 'EXT:base_types/Resources/Public/Icons/degree.svg',
                'priority' => 100,
            ],
            $registry->getCategoryType('programs', 'degree')?->toArray(),
        );
    }

    /**
     * The cache holds the flat list the loader wrote, and the registry sorts again when the
     * types are attached from it. The order therefore does not depend on the order of a
     * cache entry, not even of one written before the types were sorted.
     */
    #[Test]
    public function typesRestoredFromTheCacheAreOrderedByPriority(): void
    {
        $categoryTypes = $this->subject('base_types', 'priority_override')->loadUncached();
        $cache = $this->createMock(PhpFrontend::class);
        $cache->method('require')->willReturn($categoryTypes);
        $cache->expects($this->never())->method('set');
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->expects($this->never())->method('getActivePackages');

        $registry = (new CategoryTypeLoader($cache, $packageManager))->load();

        $this->assertSame(['degree', 'research_field'], $registry->getCategoryTypeIdentifierByGroup('programs'));
    }

    /**
     * Without `useExisting` a later declaration of the same type replaces it as a
     * whole: what it leaves out falls back to the default rather than to the earlier
     * value. The type keeps its position, because the key already exists.
     */
    #[Test]
    public function redeclaringATypeReplacesItAsAWhole(): void
    {
        $categoryTypes = $this->subject('base_types', 'redeclaring_extension')->loadUncached();

        $this->assertSame(['programs.research_field', 'programs.degree'], array_keys($categoryTypes));
        $this->assertSame(
            [
                'identifier' => 'research_field',
                'extensionKey' => 'redeclaring_extension',
                'title' => 'Subject area',
                'group' => 'programs',
                'icon' => '',
                'priority' => 0,
            ],
            $categoryTypes['programs.research_field']->toArray(),
        );
    }

    /**
     * Order decides here too: an override loaded before the package that declares the
     * type fails exactly like one of a type nobody declares.
     */
    #[Test]
    public function overrideBeforeTheDefinitionIsRejected(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1678979375330);
        $this->expectExceptionMessage('Category type does not exist for override.');

        $this->subject('overriding_extension', 'base_types')->loadUncached();
    }

    /**
     * A removal takes the type away for every package loaded after it, an override
     * included.
     */
    #[Test]
    public function overrideOfARemovedTypeIsRejected(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1678979375330);
        $this->expectExceptionMessage('Category type does not exist for override.');

        $this->subject('base_types', 'removing_extension', 'removed_type_override')->loadUncached();
    }

    #[Test]
    public function overridingATypeThatWasNeverDefinedIsRejected(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1678979375330);
        $this->expectExceptionMessage('Category type does not exist for override.');

        $this->subject('orphan_override')->loadUncached();
    }

    #[Test]
    public function typeWithoutAnIdentifierIsRejected(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1678979375330);
        $this->expectExceptionMessage('Category type identifier has to be defined as a non-empty string.');

        $this->subject('no_identifier')->loadUncached();
    }

    #[Test]
    public function typeWithoutAGroupIsRejected(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1678979375330);
        $this->expectExceptionMessage('Category type group has to be defined as a non-empty string.');

        $this->subject('no_group')->loadUncached();
    }
}
