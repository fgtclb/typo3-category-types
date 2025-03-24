<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Domain\Model;

class CategoryTypeGroup
{
    public function __construct(
        protected string $identifier = '',
        protected string $group = '',
        protected int $priority = 0,
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

    /**
     * @return array{
     *     identifier: string,
     *     group: string,
     *     priority: int,
     * }
     */
    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'group' => $this->group,
            'priority' => $this->priority,
        ];
    }

    /**
     * @param array{
     *     identifier?: string,
     *     group: string,
     *     priority: int,
     * }|array<string, mixed> $array
     * @return self
     */
    public function fromArray(array $array): self
    {
        return new self(
            identifier: (string)($array['identifier'] ?? ''),
            group: (string)($array['group'] ?? ''),
            priority: (int)($array['priority'] ?? 0),
        );
    }
}
