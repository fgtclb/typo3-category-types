<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\Loader;

use FGTCLB\CategoryTypes\Loader\CategoryTypeLoader;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use FGTCLB\CategoryTypes\Tests\Functional\AbstractCategoryTypesTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Covers `CategoryTypeLoader::load()` against a real package manager and a real core
 * cache — the wiring around the YAML handling, which is covered on its own in the unit
 * counterpart.
 *
 * `EXT:category_types` registers no category type itself, so everything asserted here
 * comes from the `test_category_types_group` fixture extension.
 */
final class CategoryTypeLoaderTest extends AbstractCategoryTypesTestCase
{
    protected function setUp(): void
    {
        $this->addTestExtension('tests/category-types-group');
        parent::setUp();
    }

    private function coreCache(): PhpFrontend
    {
        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('core');
        $this->assertInstanceOf(PhpFrontend::class, $cache);

        return $cache;
    }

    /**
     * A loader that sees no package at all, so anything it returns can only come from
     * the cache the previous loader wrote.
     */
    private function loaderWithoutPackages(): CategoryTypeLoader
    {
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getActivePackages')->willReturn([]);

        return new CategoryTypeLoader($this->coreCache(), $packageManager);
    }

    /**
     * @return string[]
     */
    private function identifiersOf(CategoryTypeRegistry $registry): array
    {
        $identifiers = [];
        foreach ($registry->getCategoryTypes() as $categoryType) {
            $identifiers[] = $categoryType->getGroup() . '.' . $categoryType->getIdentifier();
        }
        sort($identifiers);

        return $identifiers;
    }

    #[Test]
    public function loadCollectsTheTypesOfTheActivePackages(): void
    {
        $registry = $this->get(CategoryTypeLoader::class)->load();

        $this->assertSame(
            ['testing.testing_first', 'testing.testing_second'],
            $this->identifiersOf($registry),
        );
        $this->assertSame(
            'test_category_types_group',
            $registry->getCategoryType('testing', 'testing_first')?->getExtensionKey(),
        );
    }

    /**
     * The registry the container hands out is the one the loader built — `Services.yaml`
     * registers `CategoryTypeRegistry` as a service produced by this factory method.
     */
    #[Test]
    public function containerRegistryIsTheLoadedOne(): void
    {
        $this->assertSame(
            $this->identifiersOf($this->get(CategoryTypeLoader::class)->load()),
            $this->identifiersOf($this->get(CategoryTypeRegistry::class)),
        );
    }

    #[Test]
    public function repeatedLoadReturnsTheSameRegistryInstance(): void
    {
        $loader = $this->get(CategoryTypeLoader::class);

        $this->assertSame($loader->load(), $loader->load());
    }

    /**
     * The point of the cache: a second request does not read a single YAML file. The
     * types are restored from the exported PHP through `CategoryType::__set_state()`,
     * which is why that method has to keep working even though nothing calls it directly.
     */
    #[Test]
    public function typesAreRestoredFromTheCacheWithoutReadingAnyPackage(): void
    {
        $this->get(CategoryTypeLoader::class)->load();

        $cachedRegistry = $this->loaderWithoutPackages()->load();

        $this->assertSame(
            ['testing.testing_first', 'testing.testing_second'],
            $this->identifiersOf($cachedRegistry),
        );
        $this->assertSame(
            'Testing first',
            $cachedRegistry->getCategoryType('testing', 'testing_first')?->getTitle(),
        );
    }

    /**
     * Without a cache entry the same loader finds nothing, which is what makes the test
     * above evidence for the cache rather than for the fixture extension.
     */
    #[Test]
    public function loaderWithoutPackagesFindsNothingWhenTheCacheIsEmpty(): void
    {
        $this->coreCache()->flush();

        $this->assertSame([], $this->identifiersOf($this->loaderWithoutPackages()->load()));
    }

    /**
     * An installation where no extension ships category types: the registry stays empty
     * and usable. Its group lookups then report an unknown group rather than failing on
     * an uninitialised property.
     */
    #[Test]
    public function emptyRegistryStaysUsable(): void
    {
        $this->coreCache()->flush();

        $registry = $this->loaderWithoutPackages()->load();

        $this->assertSame([], $registry->getCategoryTypes());
        $this->assertSame([], $registry->getGroupedCategoryTypes());
        $this->assertSame([], $registry->toArray());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1683633304209);
        $registry->getCategoryTypesByGroup('testing');
    }
}
