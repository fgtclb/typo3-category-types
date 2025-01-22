<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Domain\Model;

class CategoryTypeGroup
{
    protected string $identifier;

    protected string $group;

    protected int $priority;

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

    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'group' => $this->group,
            'priority' => $this->priority,
        ];
    }
}
