<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Domain\Model;

class CategoryType
{
    public function __construct(
        private string $identifier,
        private string $group,
        private int $priority,
    ) {}

    public static function fromArray(array $array): CategoryType
    {
        return new self(
            identifier: (string)($array['identifier'] ?? ''),
            group: (string)($array['group'] ?? ''),
            priority: (int)($array['priority'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'group' => $this->group,
            'priority' => $this->priority,
        ];
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
