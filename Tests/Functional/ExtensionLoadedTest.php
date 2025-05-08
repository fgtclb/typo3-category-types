<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

final class ExtensionLoadedTest extends AbstractCategoryTypesTestCase
{
    #[Test]
    public function testCaseLoadsExtension(): void
    {
        $this->assertContains('fgtclb/category-types', $this->testExtensionsToLoad);
    }

    #[Test]
    public function extensionIsLoaded(): void
    {
        $this->assertTrue(ExtensionManagementUtility::isLoaded('category_types'));
    }
}
