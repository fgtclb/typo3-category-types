<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Filter;

/**
 * The category types a list filter offers, in the order it offers them, as identifiers of
 * the list's category collection. Built by {@see FilterTypeResolver}.
 *
 * `visible` are the filters shown right away. `more` are the ones a list may put behind a
 * disclosure; it is empty unless a visible count was asked for.
 *
 * @internal Built by the resolver only. The two getters are what a list template reads, as
 *           `{filterTypes.visible}` and `{filterTypes.more}`, and stay as they are.
 */
final readonly class FilterTypes
{
    /**
     * @param list<string> $visible
     * @param list<string> $more
     */
    public function __construct(
        private array $visible,
        private array $more,
    ) {}

    /**
     * @return list<string>
     */
    public function getVisible(): array
    {
        return $this->visible;
    }

    /**
     * @return list<string>
     */
    public function getMore(): array
    {
        return $this->more;
    }
}
