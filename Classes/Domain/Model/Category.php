<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Domain\Model;

use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Category
{
    protected ?CategoryType $type;
    protected ?CategoryCollection $children = null;

    public function __construct(
        protected int $uid,
        protected int $parentId,
        protected string $title,
        string $type = 'default',
        string $typeGroup = 'default',
        protected bool $disabled = false
    ) {
        $this->type = null;
        if ($type !== 'default') {
            $this->type = GeneralUtility::makeInstance(CategoryTypeRegistry::class)
                ->getCategoryType($typeGroup, $type);
        }
        // @todo Question for JPH - what to do with this code ? Or move to getter as lazy load ?
        //$this->children = GeneralUtility::makeInstance(CategoryRepository::class)->findChildren($this->uid);
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }

    public function getType(): ?CategoryType
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getChildren(): ?CategoryCollection
    {
        return $this->children;
    }

    public function hasParent(): bool
    {
        return $this->parentId > 0;
    }

    public function getParent(): ?Category
    {
        if (!$this->hasParent()) {
            return null;
        }

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
        return $categoryRepository->findParent(
            $this->type?->getGroup() ?? 'default',
            $this->parentId
        );
    }

    public function isRoot(): bool
    {
        $parent = $this->getParent();
        if ($parent === null
            || (string)$this->type !== (string)$parent->getType()
        ) {
            return true;
        }
        return false;
    }

    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function __toString(): string
    {
        return (string)$this->uid;
    }
}
