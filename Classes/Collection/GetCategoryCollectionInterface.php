<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Collection;

/**
 * Interface to ensure that expected entities provides a concrete method
 * to retrieve the CategoryCollection and are CategoryCollection aware.
 */
interface GetCategoryCollectionInterface
{
    public function getAttributes(): CategoryCollection;
}
