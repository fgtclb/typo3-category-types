<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Domain\Model;

/**
 * A group declared in the `groups:` section of a `Configuration/CategoryTypes.yaml`. Its
 * title heads the types of the group in the type select of a category, and its icon is
 * registered as `category_types.group.<identifier>`.
 */
class CategoryTypeGroup
{
    public function __construct(
        protected string $identifier = '',
        protected string $group = '',
        protected int $priority = 0,
        protected string $title = '',
        protected string $icon = '',
        protected bool $inlineIcon = false,
    ) {}

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setGroup(string $group): void
    {
        $this->group = $group;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setIcon(string $icon): void
    {
        $this->icon = $icon;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getIconIdentifier(): string
    {
        return 'category_types.group.' . $this->identifier;
    }

    public function setInlineIcon(bool $inlineIcon): void
    {
        $this->inlineIcon = $inlineIcon;
    }

    public function isInlineIcon(): bool
    {
        return $this->inlineIcon;
    }

    /**
     * @return array{
     *     identifier: string,
     *     group: string,
     *     priority: int,
     *     title: string,
     *     icon: string,
     *     inlineIcon: bool,
     * }
     */
    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'group' => $this->group,
            'priority' => $this->priority,
            'title' => $this->title,
            'icon' => $this->icon,
            'inlineIcon' => $this->inlineIcon,
        ];
    }

    /**
     * @param array{
     *     identifier?: string,
     *     group?: string,
     *     priority?: int,
     *     title?: string,
     *     icon?: string,
     *     inlineIcon?: bool,
     * }|array<string, mixed> $array
     * @return self
     */
    public static function fromArray(array $array): self
    {
        return new self(
            identifier: (string)($array['identifier'] ?? ''),
            group: (string)($array['group'] ?? ''),
            priority: (int)($array['priority'] ?? 0),
            title: (string)($array['title'] ?? ''),
            icon: (string)($array['icon'] ?? ''),
            inlineIcon: (bool)($array['inlineIcon'] ?? false),
        );
    }

    /**
     * Restores a group from the cache entry the loader writes with `var_export()`.
     *
     * @param array<string, mixed> $array
     */
    public static function __set_state(array $array): self
    {
        return self::fromArray($array);
    }
}
