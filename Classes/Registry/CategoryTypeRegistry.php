<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Registry;

use FGTCLB\CategoryTypes\Domain\Model\CategoryType;
use FGTCLB\CategoryTypes\Exception\CategoryTypeExistException;
use InvalidArgumentException;

class CategoryTypeRegistry
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
     * @param CategoryType $categoryType
     */
    public function attach(CategoryType $categoryType): void
    {
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
        return $this->registry;
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
     */
    public function getCategoryTypesByGroup(string $group): array
    {
        if (!array_key_exists($group, $this->groupedRegistry)) {
            throw new InvalidArgumentException(
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
     * @return CategoryType[]
     */
    public function getCategoryTypeIdentifierByGroup(string $group): array
    {
        if (!array_key_exists($group, $this->groupedRegistry)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Group "%s" does not exits in registry.',
                    $group,
                ),
                1683633304209
            );
        }

        return array_keys($this->groupedRegistry[$group]);
    }

    /**
     * @param CategoryType $categoryType
     * @return bool
     */
    public function exists(CategoryType $categoryType): bool
    {
        return in_array($categoryType, $this->registry, false);
    }
}
