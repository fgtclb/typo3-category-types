<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Registry;

use FGTCLB\CategoryTypes\Domain\Model\CategoryType;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
final class CategoryTypeRegistry
{
    /**
     * @var CategoryType[]
     */
    private array $categoryTypes = [];

    public function register(CategoryType $categoryType): void
    {
        $this->categoryTypes[$categoryType->getIdentifier()] = $categoryType;
    }

    public function getCategoryTypes(): array
    {
        return $this->categoryTypes;
    }
}
