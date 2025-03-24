<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Registry;

use FGTCLB\CategoryTypes\Domain\Model\CategoryType;
use FGTCLB\CategoryTypes\Exception\CategoryTypeExistException;

class CategoryTypeRegistry implements \JsonSerializable
{
    /**
     * @var CategoryType[]
     */
    protected array $registry = [];

    /**
     * @var array<string, CategoryType[]>
     */
    protected array $groupedRegistry;

    /**
     * @param CategoryType ...$categoryTypes
     */
    public function attach(CategoryType ...$categoryTypes): void
    {
        if ($categoryTypes === []) {
            return;
        }
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

            $this->registry[] = $categoryType;
            $this->groupedRegistry[$groupIdentifier][$typeIdentifier] = $categoryType;
        }
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
