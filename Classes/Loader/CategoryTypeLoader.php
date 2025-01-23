<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Loader;

use FGTCLB\CategoryTypes\Domain\Model\CategoryType;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\PackageManager;

class CategoryTypeLoader
{
    protected CategoryTypeRegistry $categoryTypeRegistry;

    public function __construct(
        #[Autowire(service: 'cache.core')]
        protected readonly PhpFrontend $cache,
        protected readonly PackageManager $packageManager
    ) {}

    public function load(): CategoryTypeRegistry
    {
        if (isset($this->categoryTypeRegistry)) {
            return $this->categoryTypeRegistry;
        }

        if (is_array($categoryTypes = $this->getFromCache())) {
            $categoryTypes = array_map(fn(array $categoryType): CategoryType => CategoryType::fromArray($categoryType), $categoryTypes);
            $this->categoryTypeRegistry = $this->fillCategoryTypeRegistry($categoryTypes);
            return $this->categoryTypeRegistry;
        }

        $this->categoryTypeRegistry = $this->loadUncached();
        $this->setCache();
        return $this->categoryTypeRegistry;
    }

    public function loadUncached(): CategoryTypeRegistry
    {
        $loadedCategoryTypes = [];

        foreach ($this->packageManager->getActivePackages() as $package) {
            $extensionKey = $package->getPackageKey();
            $typeConfigurationFile = $package->getPackagePath() . '/Configuration/CategoryTypes.yaml';

            if (file_exists($typeConfigurationFile)) {
                $configArray = Yaml::parseFile($typeConfigurationFile);
                if ($configArray === null) {
                    continue;
                }
                if (array_key_exists('types', $configArray) && is_array($configArray['types'])) {
                    foreach ($configArray['types'] as $categoryType) {
                        $categoryType['extensionKey'] = $extensionKey;
                        $loadedCategoryTypes[] = CategoryType::fromArray($categoryType);
                    }
                }
            }
        }

        $this->categoryTypeRegistry = $this->fillCategoryTypeRegistry($loadedCategoryTypes);

        return $this->categoryTypeRegistry;
    }

    /**
     * @param CategoryType[] $categoryTypes
     */
    protected function fillCategoryTypeRegistry(array $categoryTypes): CategoryTypeRegistry
    {
        $categoryTypeRegistry = new CategoryTypeRegistry();
        foreach ($categoryTypes as $categoryType) {
            $categoryTypeRegistry->register($categoryType);
        }
        return $categoryTypeRegistry;
    }

    protected function getFromCache(): false|array
    {
        return $this->cache->require('CategoryTypes_Types');
    }

    protected function setCache(): void
    {
        $cache = array_map(fn(CategoryType $categoryType): array => $categoryType->toArray(), $this->categoryTypeRegistry->getCategoryTypes());
        $this->cache->set('CategoryTypes_Types', 'return ' . var_export($cache, true) . ';');
    }
}
