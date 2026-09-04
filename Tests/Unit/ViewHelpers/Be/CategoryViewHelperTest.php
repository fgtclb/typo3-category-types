<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Unit\ViewHelpers\Be;

use FGTCLB\CategoryTypes\ViewHelpers\Be\CategoryViewHelper;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * `render()` used to dereference `$this->renderingContext` unconditionally, although Fluid
 * types it `RenderingContextInterface|null` on `AbstractViewHelper` and leaves it `null`
 * until `setRenderingContext()` is called. A view helper instantiated and rendered outside
 * the regular compile/render cycle - this test, or any other caller that skips
 * `ViewHelperInvoker` - hit a fatal error on the first `getVariableProvider()` call.
 */
final class CategoryViewHelperTest extends UnitTestCase
{
    #[Test]
    public function renderReturnsEmptyStringWithoutARenderingContext(): void
    {
        $subject = new CategoryViewHelper();

        $this->assertSame('', $subject->render());
    }
}
