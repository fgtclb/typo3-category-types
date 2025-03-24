<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Factory;

use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;

class CategoryCollectionFactory
{
    public function __construct(
        private readonly CategoryTypeRegistry $categoryTypeRegistry,
    ) {}

    /**
     * @param string $group
     * @return CategoryCollection
     */
    public function createCategoryCollection(string $group): CategoryCollection
    {
        $categoryCollection = new CategoryCollection();
        $categoryCollection->setTypeIdentifiers($this->categoryTypeRegistry->getCategoryTypeIdentifierByGroup($group));

        return $categoryCollection;
    }
}
