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
 * `load()` and the cache round trip are covered by the functional counterpart, which has
 * a real cache backend.
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
