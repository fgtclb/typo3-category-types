<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Registry;

use FGTCLB\CategoryTypes\Domain\Model\CategoryType;
use FGTCLB\CategoryTypes\Exception\CategoryTypeExistException;

/**
 * @api
 */
class CategoryTypeRegistry implements \JsonSerializable
{
    /**
     * @var CategoryType[]
     */
    protected array $registry = [];

    /**
     * @var array<string, CategoryType[]>
     */
    protected array $groupedRegistry = [];

    /**
     * Attaches the types and orders every group by priority, highest first. Types of equal
     * priority keep the order they were attached in, which is the load order of their
     * packages and their position in the file.
     *
     * @param CategoryType ...$categoryTypes
     */
    public function attach(CategoryType ...$categoryTypes): void
    {
        if ($categoryTypes === []) {
            return;
        }
        try {
            foreach ($categoryTypes as $categoryType) {
                $typeIdentifier = (string)$categoryType->getIdentifier();
                $groupIdentifier = $categoryType->getGroup() ? (string)$categoryType->getGroup() : 'default';
                if (!isset($this->groupedRegistry[$groupIdentifier])) {
                    $this->groupedRegistry[$groupIdentifier] = [];
                } else {
                    if (array_key_exists($categoryType->getIdentifier(), $this->groupedRegistry[$groupIdentifier])) {
                        throw new CategoryTypeExistException(
                            'Category type already defined in registry.',
                            1678979375329
                        );
                    }
                }

                $this->groupedRegistry[$groupIdentifier][$typeIdentifier] = $categoryType;
            }
        } finally {
            // A rejected duplicate leaves the types attached before it, as it always
            // did, so the flat list has to include them as well.
            $this->sortByPriority();
        }
    }

    /**
     * Sorting here rather than in the loader covers every way in: the YAML files, the
     * cache entry and a direct `attach()`. `uasort()` is stable, so equal priorities need
     * no tiebreaker. The flat list is rebuilt from the groups, in the order each group was
     * first attached.
     */
    private function sortByPriority(): void
    {
        $registry = [];
        foreach ($this->groupedRegistry as $groupIdentifier => $categoryTypes) {
            uasort(
                $categoryTypes,
                static fn(CategoryType $a, CategoryType $b): int => $b->getPriority() <=> $a->getPriority(),
            );
            $this->groupedRegistry[$groupIdentifier] = $categoryTypes;
            foreach ($categoryTypes as $categoryType) {
                $registry[] = $categoryType;
            }
        }
        $this->registry = $registry;
    }

    /**
     * @return ?CategoryType
     */
    public function getCategoryType(string $groupIdentifier, string $typeIdentifier): ?CategoryType
    {
        if (!isset($this->groupedRegistry[$groupIdentifier][$typeIdentifier])) {
            return null;
        }

        return $this->groupedRegistry[$groupIdentifier][$typeIdentifier];
    }

    /**
     * @return CategoryType[]
     */
    public function getCategoryTypes(): array
    {
        return array_values($this->registry);
    }

    /**
      * @return array<string, CategoryType[]>
      */
    public function getGroupedCategoryTypes(): array
    {
        return $this->groupedRegistry;
    }

    /**
     * @param string $group
     * @return CategoryType[]
     * @throws \InvalidArgumentException if group $group does not exists.
     * @todo Reconsider if throwing an exception in case group does not exists is really the way to communicate here.
     */
    public function getCategoryTypesByGroup(string $group): array
    {
        if (!array_key_exists($group, $this->groupedRegistry)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Group "%s" does not exits in registry.',
                    $group,
                ),
                1683633304209
            );
        }

        return $this->groupedRegistry[$group];
    }

    /**
     * @param string $group
     * @return string[]
     * @throws \InvalidArgumentException if group $group does not exists.
     * @todo Reconsider if throwing an exception in case group does not exists is really the way to communicate here.
     */
    public function getCategoryTypeIdentifierByGroup(string $group): array
    {
        if (!array_key_exists($group, $this->groupedRegistry)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Group "%s" does not exits in registry.',
                    $group,
                ),
                1683633304209
            );
        }

        /** @var string[] $keys */
        $keys = array_keys($this->groupedRegistry[$group]);
        return $keys;
    }

    /**
     * @param CategoryType $categoryType
     * @return bool
     */
    public function exists(CategoryType $categoryType): bool
    {
        return in_array($categoryType, $this->registry, false);
    }

    /**
     * @return array<string, array<array{
     *      identifier: string,
     *      extensionKey: string,
     *      title: string,
     *      group: string,
     *      icon: string,
     *      priority: int,
     *      inlineIcon: bool,
     *  }>>
     */
    public function toArray(): array
    {
        $array = [];
        foreach ($this->groupedRegistry as $group => $groupItems) {
            $array[$group] ??= [];
            foreach ($groupItems as $groupItem) {
                $array[$group][] = $groupItem->toArray();
            }
        }
        return $array;
    }

    /**
     * @return array{registry: CategoryType[]}
     */
    public function jsonSerialize(): array
    {
        return [
            'registry' => array_values($this->registry),
        ];
    }
}
