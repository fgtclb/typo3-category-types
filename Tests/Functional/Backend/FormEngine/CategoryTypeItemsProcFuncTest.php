<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\Backend\FormEngine;

use FGTCLB\CategoryTypes\Backend\FormEngine\CategoryTypeItemsProcFunc;
use FGTCLB\CategoryTypes\Tests\Functional\AbstractCategoryTypesTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Core resolves an `itemsProcFunc` through `GeneralUtility::makeInstance()`, which hands out
 * a service with its constructor arguments only when the container marks it public; a
 * private one would be built with `new` and fail on the missing registry. The field of the
 * program list plugin is covered by `FilterTypesFieldTest` of `EXT:academic_programs`.
 */
final class CategoryTypeItemsProcFuncTest extends AbstractCategoryTypesTestCase
{
    protected function setUp(): void
    {
        $this->addTestExtension('tests/category-types-group');
        parent::setUp();
    }

    #[Test]
    public function coreGetsTheProviderFromTheContainer(): void
    {
        $this->assertSame(
            $this->get(CategoryTypeItemsProcFunc::class),
            GeneralUtility::makeInstance(CategoryTypeItemsProcFunc::class),
        );
    }

    #[Test]
    public function theProviderOffersTheTypesOfARegisteredGroup(): void
    {
        $params = ['items' => [], 'config' => ['itemsProcConfig' => ['group' => 'testing']]];

        GeneralUtility::makeInstance(CategoryTypeItemsProcFunc::class)->itemsForGroup($params);

        $this->assertSame(
            [
                ['label' => 'Testing first', 'value' => 'testing_first', 'icon' => 'category_types.testing.testing_first'],
                ['label' => 'Testing second', 'value' => 'testing_second', 'icon' => 'category_types.testing.testing_second'],
            ],
            $params['items'],
        );
    }
}
