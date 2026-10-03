<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Loader;

use FGTCLB\CategoryTypes\Domain\Model\CategoryType;
use FGTCLB\CategoryTypes\Domain\Model\CategoryTypeGroup;
use FGTCLB\CategoryTypes\Exception\CategoryTypeException;
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
        $registry = new CategoryTypeRegistry();
        $this->categoryTypeRegistry = $registry;

        // Load cached category types
        $categoryTypes = $this->getFromCache();
        if (is_array($categoryTypes)) {
            $registry->attach(...array_values($categoryTypes));
        } else {
            // Load from extension yaml files and populate cache
            $categoryTypes = $this->loadUncached();
            $registry->attach(...array_values($categoryTypes));
            $this->setCache(...array_values($registry->getCategoryTypes()));
        }

        // Groups have a cache entry of their own, so an entry written before groups were
        // read misses and loads rather than reading as "no groups".
        $groups = $this->getGroupsFromCache();
        if (!is_array($groups)) {
            $groups = $this->loadGroupsUncached();
            $this->setGroupsCache(...array_values($groups));
        }
        $registry->attachGroups(...array_values($groups));

        return $registry;
    }

    /**
     * @return CategoryType[]
     */
    public function loadUncached(): array
    {
        $loadedCategoryTypes = [];
        foreach ($this->readConfigurationFiles() as $extensionKey => $configArray) {
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
                        // A new frontend file does not inherit the inline flag the earlier
                        // declaration set for its own frontend file. A new `icon` keeps the
                        // earlier `inlineIcon`, the documented rule of the backend pair.
                        if (array_key_exists('frontendIcon', $categoryType) && !array_key_exists('frontendInlineIcon', $categoryType)) {
                            $categoryType['frontendInlineIcon'] = null;
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
        $this->assertIdentifiersAreUniqueAcrossGroups($loadedCategoryTypes);
        return $loadedCategoryTypes;
    }

    /**
     * Reads the `groups:` section of every active package, in package load order. A later
     * package replaces the title, the icon, `inlineIcon`, `frontendIcon`,
     * `frontendInlineIcon` and the priority it declares and keeps what it leaves out, so a
     * project can relabel a shipped group without restating its icon. A new `frontendIcon`
     * without `frontendInlineIcon` drops the earlier frontend flag. A redeclared group keeps
     * its position.
     *
     * @return array<string, CategoryTypeGroup> Keyed by the identifier without surrounding
     *                                          spaces.
     * @throws CategoryTypeException
     */
    public function loadGroupsUncached(): array
    {
        $loadedGroups = [];
        foreach ($this->readConfigurationFiles() as $configArray) {
            if (!is_array($configArray['groups'] ?? null)) {
                continue;
            }
            foreach ($configArray['groups'] as $declaration) {
                $identifier = is_array($declaration) ? ($declaration['identifier'] ?? null) : null;
                if (!is_string($identifier) || trim($identifier, ' ') === '') {
                    throw new CategoryTypeException(
                        'Category type group identifier has to be defined as a non-empty string.',
                        1790592001
                    );
                }
                /** @var array<string, mixed> $declaration */
                $identifier = trim($identifier, ' ');
                $group = $loadedGroups[$identifier] ?? new CategoryTypeGroup($identifier);
                if (is_string($declaration['title'] ?? null) && $declaration['title'] !== '') {
                    $group->setTitle($declaration['title']);
                }
                if (is_string($declaration['icon'] ?? null) && $declaration['icon'] !== '') {
                    $group->setIcon($declaration['icon']);
                }
                if (array_key_exists('inlineIcon', $declaration)) {
                    $group->setInlineIcon((bool)$declaration['inlineIcon']);
                }
                if (is_string($declaration['frontendIcon'] ?? null) && $declaration['frontendIcon'] !== '') {
                    $group->setFrontendIcon($declaration['frontendIcon']);
                    // A new frontend file does not inherit the inline flag of the file it
                    // replaces, unlike `icon`, which keeps the earlier `inlineIcon`.
                    $group->setFrontendInlineIcon(null);
                }
                if (array_key_exists('frontendInlineIcon', $declaration)) {
                    $group->setFrontendInlineIcon(
                        $declaration['frontendInlineIcon'] === null ? null : (bool)$declaration['frontendInlineIcon']
                    );
                }
                if (array_key_exists('priority', $declaration)) {
                    $group->setPriority((int)$declaration['priority']);
                }
                $loadedGroups[$identifier] = $group;
            }
        }
        return $loadedGroups;
    }

    /**
     * @return \Generator<string, array<mixed>> The parsed `Configuration/CategoryTypes.yaml`
     *                                          of every active package that has one, keyed
     *                                          by extension key, in package load order.
     */
    private function readConfigurationFiles(): \Generator
    {
        foreach ($this->packageManager->getActivePackages() as $package) {
            $configurationFile = $package->getPackagePath() . '/Configuration/CategoryTypes.yaml';
            if (!file_exists($configurationFile)) {
                continue;
            }
            $configArray = Yaml::parseFile($configurationFile);
            if (!is_array($configArray)) {
                continue;
            }
            yield $package->getPackageKey() => $configArray;
        }
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

    /**
     * @return array<string, CategoryTypeGroup>|null
     */
    protected function getGroupsFromCache(): ?array
    {
        $groups = $this->cache->require($this->categoryTypesGroupsIdentifier());
        if (!is_array($groups)) {
            return null;
        }
        $restoredGroups = [];
        foreach ($groups as $group) {
            if ($group instanceof CategoryTypeGroup) {
                $restoredGroups[$group->getIdentifier()] = $group;
            }
        }
        return $restoredGroups;
    }

    protected function setGroupsCache(CategoryTypeGroup ...$groups): void
    {
        $this->cache->set($this->categoryTypesGroupsIdentifier(), 'return ' . var_export($groups, true) . ';');
    }

    /**
     * @return non-empty-string
     */
    protected function categoryTypesGroupsIdentifier(): string
    {
        return 'CategoryTypes_Groups';
    }
}
