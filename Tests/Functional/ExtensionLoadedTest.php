<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional;

use FGTCLB\TestingHelper\FunctionalTestCase\ExtensionsLoadedTestsTrait;

final class ExtensionLoadedTest extends AbstractCategoryTypesTestCase
{
    use ExtensionsLoadedTestsTrait;

    private static $expectedLoadedExtensions = [
        // composer package names
        'fgtclb/academic-base',
        'fgtclb/category-types',
        // extension keys
        'academic_base',
        'category_types',
    ];
}
