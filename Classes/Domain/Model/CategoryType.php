<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Domain\Model;

/**
 * @api
 */
class CategoryType implements \JsonSerializable, \Stringable
{
    public function __construct(
        protected readonly string $identifier,
        protected readonly string $extensionKey,
        protected readonly string $title,
        protected readonly string $group,
        protected readonly string $icon,
        protected readonly int $priority,
        protected readonly bool $inlineIcon = false,
        protected readonly string $frontendIcon = '',
        protected readonly ?bool $frontendInlineIcon = null,
    ) {}

    /**
     * @param array{
     *     identifier?: string,
     *     extensionKey?: string,
     *     title?: string,
     *     group?: string,
     *     icon?: string,
     *     priority?: int,
     *     inlineIcon?: bool,
     *     frontendIcon?: string,
     *     frontendInlineIcon?: bool|null,
     * } $array
     * @return CategoryType
     */
    public static function __set_state(array $array): self
    {
        return new self(
            identifier: (string)($array['identifier'] ?? ''),
            extensionKey: (string)($array['extensionKey'] ?? ''),
            title: (string)($array['title'] ?? ''),
            group: (string)($array['group'] ?? ''),
            icon: (string)($array['icon'] ?? ''),
            priority: (int)($array['priority'] ?? 0),
            inlineIcon: (bool)($array['inlineIcon'] ?? false),
            frontendIcon: (string)($array['frontendIcon'] ?? ''),
            frontendInlineIcon: isset($array['frontendInlineIcon']) ? (bool)$array['frontendInlineIcon'] : null,
        );
    }

    /**
     * Used by {@see CategoryTypeLoader} on the uncached path, together with
     * {@see CategoryType::toArray()}. {@see CategoryType::__set_state()} serves the
     * cached path, where the loader requires a var_exported file - it did not replace
     * these two.
     *
     * @param array{
     *     identifier?: string,
     *     extensionKey?: string,
     *     title?: string,
     *     group?: string,
     *     icon?: string,
     *     priority?: int,
     *     inlineIcon?: bool,
     *     frontendIcon?: string,
     *     frontendInlineIcon?: bool|null,
     * }|array<string, mixed> $array
     * @return CategoryType
     */
    public static function fromArray(array $array): CategoryType
    {
        return new self(
            identifier: (string)($array['identifier'] ?? ''),
            extensionKey: (string)($array['extensionKey'] ?? ''),
            title: (string)($array['title'] ?? ''),
            group: (string)($array['group'] ?? ''),
            icon: (string)($array['icon'] ?? ''),
            priority: (int)($array['priority'] ?? 0),
            inlineIcon: (bool)($array['inlineIcon'] ?? false),
            frontendIcon: (string)($array['frontendIcon'] ?? ''),
            frontendInlineIcon: isset($array['frontendInlineIcon']) ? (bool)$array['frontendInlineIcon'] : null,
        );
    }

    /**
     * Used by {@see CategoryTypeLoader} on the uncached path, together with
     * {@see CategoryType::fromArray()}. {@see CategoryType::__set_state()} serves the
     * cached path, where the loader requires a var_exported file - it did not replace
     * these two.
     *
     * @return array{
     *     identifier: string,
     *     extensionKey: string,
     *     title: string,
     *     group: string,
     *     icon: string,
     *     priority: int,
     *     inlineIcon: bool,
     *     frontendIcon: string,
     *     frontendInlineIcon: bool|null,
     * }
     */
    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'extensionKey' => $this->extensionKey,
            'title' => $this->title,
            'group' => $this->group,
            'icon' => $this->icon,
            'priority' => $this->priority,
            'inlineIcon' => $this->inlineIcon,
            'frontendIcon' => $this->frontendIcon,
            'frontendInlineIcon' => $this->frontendInlineIcon,
        ];
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getExtensionKey(): string
    {
        return $this->extensionKey;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getIconIdentifier(): string
    {
        return implode('.', [
            'category_types',
            $this->group,
            $this->identifier,
        ]);
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Whether the icon file of this type may be inlined into the markup, which is what
     * makes it follow the colour of the text around it. It is opt in and it is off by
     * default, because an inlined file is part of the document: its `id` attributes and
     * its `<style>` rules are global, so two files of two unrelated vendors collide - and
     * the defaults of an Adobe Illustrator export (`id="SVGID_1_"`, `.st0`, `.st1`) make
     * that collision the normal case rather than an unlucky one. Left off, the icon keeps
     * the core provider and is rendered as an `<img>`, which is opaque to CSS and
     * therefore cannot collide with anything.
     *
     * {@see \FGTCLB\CategoryTypes\ServiceProvider::addIcons()} reads this.
     */
    public function isInlineIcon(): bool
    {
        return $this->inlineIcon;
    }

    /**
     * The file the frontend shows: `frontendIcon` when the type declares one, `icon`
     * otherwise. The backend always shows {@see CategoryType::getIcon()}.
     *
     * {@see \FGTCLB\CategoryTypes\EventListener\AddCategoryTypeFrontendIcons} reads this.
     */
    public function getFrontendIcon(): string
    {
        return $this->frontendIcon !== '' ? $this->frontendIcon : $this->icon;
    }

    /**
     * Whether the frontend inlines {@see CategoryType::getFrontendIcon()}. A declared
     * `frontendInlineIcon` decides. Without one, the frontend follows `inlineIcon` only
     * while it shows the `icon` file, because that flag was set for that file: a
     * `frontendIcon` nobody opted in for is shown as an image, for the reasons given at
     * {@see CategoryType::isInlineIcon()}.
     */
    public function isFrontendInlineIcon(): bool
    {
        if ($this->frontendInlineIcon !== null) {
            return $this->frontendInlineIcon;
        }
        return $this->frontendIcon === '' && $this->inlineIcon;
    }

    public function __toString(): string
    {
        return $this->identifier;
    }

    /**
     * @return array{
     *     identifier: string,
     *     extensionKey: string,
     *     title: string,
     *     group: string,
     *     icon: string,
     *     priority: int,
     *     inlineIcon: bool,
     *     frontendIcon: string,
     *     frontendInlineIcon: bool|null,
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'identifier' => $this->identifier,
            'extensionKey' => $this->extensionKey,
            'title' => $this->title,
            'group' => $this->group,
            'icon' => $this->icon,
            'priority' => $this->priority,
            'inlineIcon' => $this->inlineIcon,
            'frontendIcon' => $this->frontendIcon,
            'frontendInlineIcon' => $this->frontendInlineIcon,
        ];
    }
}
