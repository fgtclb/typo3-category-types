<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Domain\Model;

class CategoryType implements \JsonSerializable, \Stringable
{
    public function __construct(
        protected readonly string $identifier,
        protected readonly string $extensionKey,
        protected readonly string $title,
        protected readonly string $group,
        protected readonly string $icon,
        protected readonly int $priority,
    ) {}

    /**
     * @param array{
     *     identifier?: string,
     *     extensionKey?: string,
     *     title?: string,
     *     group?: string,
     *     icon?: string,
     *     priority?: int,
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
        ];
    }
}
