<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Loader;

use FGTCLB\CategoryTypes\Domain\Model\CategoryType;
use FGTCLB\CategoryTypes\Exception\CategoryTypeExistException;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\PackageManager;

class CategoryTypeLoader
{
    protected ?CategoryTypeRegistry $categoryTypeRegistry = null;

    public function __construct(
        #[Autowire(service: 'cache.core')]
        protected readonly PhpFrontend $cache,
        protected readonly PackageManager $packageManager
    ) {}

    public function load(): CategoryTypeRegistry
    {
        if ($this->categoryTypeRegistry !== null) {
            return $this->categoryTypeRegistry;
        }
        $this->categoryTypeRegistry = new CategoryTypeRegistry();

        // Load cached category types
        $categoryTypes = $this->getFromCache();
        if (is_array($categoryTypes)) {
            $this->categoryTypeRegistry->attach(...array_values($categoryTypes));
        } else {
            // Load from extension yaml files and populate cache
            $categoryTypes = $this->loadUncached();
            $this->categoryTypeRegistry->attach(...array_values($categoryTypes));
            $this->setCache(...array_values($this->categoryTypeRegistry->getCategoryTypes()));
        }
        // Fallback only added to satisfy phpstan. Technically not possible.
        return $this->categoryTypeRegistry ?? new CategoryTypeRegistry();
    }

    /**
     * @return CategoryType[]
     */
    public function loadUncached(): array
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
                        // @todo Consider to introduce a extracted, more complete validation/normalization method and
                        //       cover this format handling finally with tests.
                        // Check if the identifier and group of the category type to ensure it is unambiguous
                        $categoryTypeIdentifier = $categoryType['identifier'] ?? null;
                        if (!is_string($categoryTypeIdentifier) || trim($categoryTypeIdentifier, ' ') === '') {
                            throw new \Exception(
                                'Category type identifier has to be defined as a non-empty string.',
                                1678979375330
                            );
                        }
                        // @todo Validate $categoryTypeIdentifier against invalid characters, for example dots (`.`).

                        $categoryTypeGroup = $categoryType['group'] ?? null;
                        if (!is_string($categoryTypeGroup) || trim($categoryTypeGroup, ' ') === '') {
                            throw new \Exception(
                                'Category type group has to be defined as a non-empty string.',
                                1678979375330
                            );
                        }
                        // @todo Validate $categoryTypeGroup against invalid characters, for example dots (`.`).

                        // Generate an unambiguous array key for the category type
                        $categoryKey = sprintf(
                            '%s.%s',
                            trim($categoryTypeGroup, ' '),
                            trim($categoryTypeIdentifier, ' '),
                        );

                        // Remove a (default) category type if not needed in a project
                        $shouldBeRemoved = (bool)($categoryType['remove'] ?? false);
                        if ($shouldBeRemoved) {
                            unset($loadedCategoryTypes[$categoryKey]);
                            continue;
                        }

                        // The extension is added for debugging purposes
                        // @todo Temporarily ? Or should this maybe be hidden behind some kind of "context" switch ?
                        $categoryType['extensionKey'] = $extensionKey;

                        // Override a (default) category type with a custom configuration
                        $useExisting = (bool)($categoryType['useExisting'] ?? false);
                        if ($useExisting) {
                            if (!(($loadedCategoryTypes[$categoryKey] ?? null) instanceof CategoryType)) {
                                throw new \Exception(
                                    'Category type does not exist for override.',
                                    1678979375330
                                );
                            }
                            // Combine existing categoryType options with override values.
                            $categoryType = array_merge(
                                $loadedCategoryTypes[$categoryKey]->toArray(),
                                $categoryType
                            );
                        }

                        // Add category type to the list
                        $loadedCategoryTypes[$categoryKey] = CategoryType::fromArray($categoryType);
                    }
                }
            }
        }
        $this->assertIdentifiersAreUniqueAcrossGroups($loadedCategoryTypes);
        return $loadedCategoryTypes;
    }

    /**
     * A category stores the type identifier without its group, so the same identifier in
     * two groups gives the type select two items with one value, and a saved category
     * cannot tell which of the two it is. Checked once every package is read, so a later
     * package can still resolve a collision with `remove`. Not in the registry: it also
     * receives types from the cache and from direct calls, where a collision is not a
     * mistake of a package configuration.
     *
     * Identifiers are compared without surrounding whitespace and ignoring case: the
     * loader keys types by the identifier without surrounding spaces, and MySQL and
     * MariaDB compare the `type` column case-insensitively under their default
     * collation.
     *
     * @param array<string, CategoryType> $categoryTypes
     * @throws CategoryTypeExistException
     */
    private function assertIdentifiersAreUniqueAcrossGroups(array $categoryTypes): void
    {
        $declarations = [];
        foreach ($categoryTypes as $categoryType) {
            $declarations[mb_strtolower(trim($categoryType->getIdentifier()))][] = sprintf(
                '"%s" by %s',
                $categoryType->getGroup(),
                $categoryType->getExtensionKey(),
            );
        }
        foreach ($declarations as $identifier => $groups) {
            if (count($groups) > 1) {
                throw new CategoryTypeExistException(
                    sprintf(
                        'The category type identifier "%s" is declared in more than one group: %s.'
                        . ' A category stores the identifier without its group, so it has to be'
                        . ' unique across all groups.',
                        $identifier,
                        implode(', ', $groups),
                    ),
                    1790505412
                );
            }
        }
    }

    /**
     * @return CategoryType[]|null
     */
    protected function getFromCache(): ?array
    {
        $categoryTypes = $this->cache->require($this->categoryTypesTypesIdentifier());
        if (!is_array($categoryTypes)) {
            return null;
        }
        $categoryTypes = array_filter($categoryTypes, fn($value) => $value instanceof CategoryType);
        return $categoryTypes;
    }

    protected function setCache(CategoryType ...$types): void
    {
        $this->cache->set($this->categoryTypesTypesIdentifier(), 'return ' . var_export($types, true) . ';');
    }

    /**
     * @return non-empty-string string
     */
    protected function categoryTypesTypesIdentifier(): string
    {
        return 'CategoryTypes_Types';
    }
}
