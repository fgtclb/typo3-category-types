<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional;

use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;

abstract class AbstractCategoryTypesTestCase extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'fgtclb/category-types',
    ];

    /**
     * Add a fixture extension for a single test case, without restating the list above.
     * Must be called before `parent::setUp()`. Mirrors the helper of the
     * `EXT:academic_persons` test case.
     */
    protected function addTestExtension(string ...$extensions): void
    {
        foreach ($extensions as $extension) {
            if ($extension !== '' && !in_array($extension, $this->testExtensionsToLoad, true)) {
                $this->testExtensionsToLoad[] = $extension;
            }
        }
    }
}
