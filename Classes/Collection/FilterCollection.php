<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Collection;

use ArrayAccess;
use FGTCLB\CategoryTypes\Domain\Model\Category;

/**
 * @implements ArrayAccess<string, Category[]>
 * @todo Only "offsetGet" implemented, consider to change from array access to ContainerInterface (get/has only).
 */
class FilterCollection implements \ArrayAccess
{
    private CategoryCollection $filterCategories;

    public function __construct(
        ?CategoryCollection $categoryCollection = null,
    ) {
        $this->filterCategories = $categoryCollection ?? new CategoryCollection();
    }

    public function offsetExists(mixed $offset): bool
    {
        try {
            $this->filterCategories->getCategoriesByTypeName($offset);
        } catch (\InvalidArgumentException $e) {
            // @todo Needs to be replaced with a concrete exception.
            if ($e->getCode() !== 1739372162) {
                throw $e;
            }
            return false;
        }
        return true;
    }

    /**
     * @param mixed $offset
     * @return array<int, Category>|false
     */
    public function offsetGet(mixed $offset): array|false
    {
        try {
            $categories = $this->filterCategories->getCategoriesByTypeName($offset);
        } catch (\InvalidArgumentException $e) {
            // @todo Needs to be replaced with a concrete exception.
            if ($e->getCode() !== 1739372162) {
                throw $e;
            }
            return false;
        }
        return $categories;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \InvalidArgumentException(
            'Method should never be called',
            1683633632593
        );
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \InvalidArgumentException(
            'Method should never be called',
            1683633656658
        );
    }

    public function getFilterCategories(): CategoryCollection
    {
        return $this->filterCategories;
    }

    public function __toString(): string
    {
        return self::class;
    }
}
