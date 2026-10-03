<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Domain\Model;

/**
 * A group declared in the `groups:` section of a `Configuration/CategoryTypes.yaml`. Its
 * title heads the types of the group in the type select of a category, and its icon is
 * registered as `category_types_group.<identifier>`, in the icon registry of the backend
 * and in the frontend icon registry of EXT:academic_base.
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
        protected string $frontendIcon = '',
        protected ?bool $frontendInlineIcon = null,
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

    /**
     * Every type icon identifier starts with `category_types.`, every group icon identifier
     * with `category_types_group.`. The two differ in their fifteenth character, so no
     * group or type name can make a group icon identifier equal a type icon identifier.
     */
    public function getIconIdentifier(): string
    {
        return 'category_types_group.' . $this->identifier;
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
     * Sets the declared frontend file. The loader resets the declared frontend flag next
     * to it when a later declaration names a new file without one.
     */
    public function setFrontendIcon(string $frontendIcon): void
    {
        $this->frontendIcon = $frontendIcon;
    }

    /**
     * The file the frontend shows: `frontendIcon` when the group declares one, `icon`
     * otherwise, the rule of {@see CategoryType::getFrontendIcon()}.
     */
    public function getFrontendIcon(): string
    {
        return $this->frontendIcon !== '' ? $this->frontendIcon : $this->icon;
    }

    /**
     * `null` means not declared, see {@see CategoryTypeGroup::isFrontendInlineIcon()}.
     */
    public function setFrontendInlineIcon(?bool $frontendInlineIcon): void
    {
        $this->frontendInlineIcon = $frontendInlineIcon;
    }

    /**
     * Whether the frontend inlines {@see CategoryTypeGroup::getFrontendIcon()}, the rule of
     * {@see CategoryType::isFrontendInlineIcon()}: a declared `frontendInlineIcon` decides,
     * otherwise `inlineIcon` while the frontend shows the `icon` file.
     */
    public function isFrontendInlineIcon(): bool
    {
        if ($this->frontendInlineIcon !== null) {
            return $this->frontendInlineIcon;
        }
        return $this->frontendIcon === '' && $this->inlineIcon;
    }

    /**
     * @return array{
     *     identifier: string,
     *     group: string,
     *     priority: int,
     *     title: string,
     *     icon: string,
     *     inlineIcon: bool,
     *     frontendIcon: string,
     *     frontendInlineIcon: bool|null,
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
            'frontendIcon' => $this->frontendIcon,
            'frontendInlineIcon' => $this->frontendInlineIcon,
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
     *     frontendIcon?: string,
     *     frontendInlineIcon?: bool|null,
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
            frontendIcon: (string)($array['frontendIcon'] ?? ''),
            frontendInlineIcon: isset($array['frontendInlineIcon']) ? (bool)$array['frontendInlineIcon'] : null,
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
