<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\ViewHelpers;

use FGTCLB\CategoryTypes\Tests\Functional\AbstractCategoryTypesTestCase;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;

/**
 * Renders a fixture template from `Tests/Functional/Fixtures/Templates/` and returns the
 * markup, so the view helpers are asserted the way a template uses them rather than through
 * their protected methods.
 *
 * That also keeps these tests independent of how a view helper is entered: on branch `2`
 * `ViewHelpers\Be\CategoryViewHelper` implements `renderStatic()` with
 * `CompileWithRenderStatic`, here it implements `render()` - a rendering test does not see
 * the difference.
 *
 * Every call builds its own view. The views share the container, and
 * `ViewHelpers\Form\AbstractSelectViewHelper` writes and then removes a template variable
 * named `options` when `renderOptions` is `false`, which would otherwise take the assigned
 * options of the next render with it.
 */
abstract class AbstractViewHelperTestCase extends AbstractCategoryTypesTestCase
{
    /**
     * @param array<string, mixed> $variables
     */
    protected function render(string $template, array $variables = []): string
    {
        $view = $this->get(ViewFactoryInterface::class)->create(new ViewFactoryData(
            templateRootPaths: [__DIR__ . '/../Fixtures/Templates/'],
            request: $this->extbaseRequest(),
        ));
        $view->assignMultiple($variables);

        return trim($view->render($template));
    }

    /**
     * The form view helpers refuse to render without one, see
     * `TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::getRequest()`.
     */
    private function extbaseRequest(): Request
    {
        return new Request(
            (new ServerRequest())
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
                ->withAttribute('extbase', new ExtbaseRequestParameters())
        );
    }
}
