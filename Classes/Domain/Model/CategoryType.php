<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Domain\Model;

class CategoryType implements \JsonSerializable, \Stringable
{
    public function __construct(
        private readonly string $identifier,
        private readonly string $extensionKey,
        private readonly string $title,
        private readonly string $group,
        private readonly string $icon,
        private readonly int $priority,
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
     * @param array{
     *     identifier?: string,
     *     extensionKey?: string,
     *     title?: string,
     *     group?: string,
     *     icon?: string,
     *     priority?: int,
     * }|array<string, mixed> $array
     * @return CategoryType
     * @todo Original used within {@see CategoryTypeLoader}, but replaced with {@see CategoryType::__set_state()}. Check
     *       if other usages are usefill or remove this method along with {@see CategoryType::toArray()}. Other usage
     *       may be to encode/decode from json and methods are use-full in that context.
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
     * @return array{
     *     identifier: string,
     *     extensionKey: string,
     *     title: string,
     *     group: string,
     *     icon: string,
     *     priority: int,
     * }
     * @todo Original used within {@see CategoryTypeLoader}, but replaced with {@see CategoryType::__set_state()}. Check
     *       if other usages are usefill or remove this method along with {@see CategoryType::fromArray()}. Other usage
     *       may be to encode/decode from json and methods are use-full in that context.
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
