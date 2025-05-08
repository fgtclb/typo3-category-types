<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional;

use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;

abstract class AbstractCategoryTypesTestCase extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'fgtclb/category-types',
    ];
}
