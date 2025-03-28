<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Collection;

use FGTCLB\CategoryTypes\Domain\Model\Category;
use FGTCLB\CategoryTypes\Exception\CategoryExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @implements \ArrayAccess<string, Category[]>
 * @implements \Iterator<int, Category>
 * @todo Only "offsetGet" implemented, consider to change from array access to ContainerInterface (get/has only).
 */
class CategoryCollection implements \Countable, \Iterator, \ArrayAccess, \Stringable
{
    /**
     * @var Category[]
     */
    protected array $collection = [];

    /**
     * @var array<string, Category[]>
     */
    protected array $typeSortedCollection = [];

    /**
     * @var array<string>
     */
    protected array $typeIdentifiers = [];

    /**
     * @param Category $category
     */
    public function attach(Category $category): void
    {
        $categoryIdentifier = $category->getUid();

        if (array_key_exists($categoryIdentifier, $this->collection)) {
            throw new CategoryExistException(
                'Category already added to collection.',
                1739368562
            );
        }

        $this->collection[$categoryIdentifier] = $category;
    }

    /**
     * @param array<string> $typeIdentifiers
     */
    public function setTypeIdentifiers(array $typeIdentifiers): void
    {
        $this->typeIdentifiers = $typeIdentifiers;
    }

    /**
     * @return array<string, Category[]>
     */
    public function getAllCategoriesByType(): array
    {
        if (empty($this->typeIdentifiers)) {
            return [];
        }

        if (empty($this->typeSortedCollection)) {
            foreach ($this->typeIdentifiers as $typeIdentifier) {
                $this->typeSortedCollection[$typeIdentifier] = [];
            }
        }

        foreach ($this->collection as $category) {
            $categoryIdentifier = $category->getUid();
            $typeIdentifier = (string)$category->getType();
            if (in_array($typeIdentifier, $this->typeIdentifiers, true)) {
                $this->typeSortedCollection[$typeIdentifier][$categoryIdentifier] = $category;
            }
        }

        return $this->typeSortedCollection;
    }

    /**
     * @param string $typeIdentifier
     * @return Category[]
     */
    public function getCategoriesByTypeName(string $typeIdentifier): array
    {
        $typeIdentifier = GeneralUtility::camelCaseToLowerCaseUnderscored($typeIdentifier);

        if (!in_array($typeIdentifier, $this->typeIdentifiers, true)) {
            // @todo Needs to be replaced with a concrete exception.
            throw new \InvalidArgumentException(
                sprintf(
                    'Category type "%s" not ',
                    $typeIdentifier
                ),
                1739372162
            );
        }

        return $this->getAllCategoriesByType()[$typeIdentifier];
    }

    /**
     * @param string $name
     * @param array<int|string, mixed> $arguments
     * @return Category[]
     */
    public function __call(string $name, array $arguments): array
    {
        return $this->getCategoriesByTypeName($name);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * @param Category $category
     * @return bool
     */
    public function exist(Category $category): bool
    {
        return in_array($category, $this->collection, false);
    }

    /**
     * Countable method count
     * @return int
     */
    public function count(): int
    {
        return count($this->collection);
    }

    /**
     * Iterator method current
     * @return Category|false
     */
    public function current(): Category|false
    {
        return current($this->collection);
    }

    /**
     * Iterator method next
     */
    public function next(): void
    {
        next($this->collection);
    }

    /**
     * Iterator method key
     * @return string|int|null
     */
    public function key(): string|int|null
    {
        return key($this->collection);
    }

    /**
     * Iterator method valid
     * @return bool
     */
    public function valid(): bool
    {
        return current($this->collection) !== false;
    }

    /**
     * Iterator method rewind
     */
    public function rewind(): void
    {
        reset($this->collection);
    }

    /**
     * ArrayAccess method offsetExists
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        if (!is_string($offset)) {
            return false;
        }
        $lowerName = GeneralUtility::camelCaseToLowerCaseUnderscored($offset);
        return array_key_exists($lowerName, $this->typeSortedCollection);
    }

    /**
     * ArrayAccess method offsetGet
     * @return Category[]|false
     */
    public function offsetGet(mixed $offset): array|false
    {
        if (!is_string($offset)) {
            return false;
        }
        return $this->getCategoriesByTypeName($offset);
    }

    /**
     * ArrayAccess method offsetSet is not implemented
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \InvalidArgumentException(
            'Method should never be called',
            1683214236549
        );
    }

    /**
     * ArrayAccess method offsetUnset is not implemented
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new \InvalidArgumentException(
            'Method should never be called',
            1683214246022
        );
    }
}
