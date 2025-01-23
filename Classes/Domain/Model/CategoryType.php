<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Domain\Model;

class CategoryType
{
    public function __construct(
        private string $identifier,
        private string $extensionKey,
        private string $title,
        private string $group,
        private string $icon,
        private int $priority,
    ) {}

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

    public function getPriority(): int
    {
        return $this->priority;
    }
}
