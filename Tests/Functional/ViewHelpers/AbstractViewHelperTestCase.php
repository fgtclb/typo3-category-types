<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\ViewHelpers;

use FGTCLB\CategoryTypes\Tests\Functional\AbstractCategoryTypesTestCase;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3Fluid\Fluid\View\TemplateView;

/**
 * Renders a fixture template from `Tests/Functional/Fixtures/Templates/` and returns the
 * markup, so the view helpers are asserted the way a template uses them rather than through
 * their protected methods.
 *
 * That also keeps these tests independent of how a view helper is entered:
 * `ViewHelpers\Be\CategoryViewHelper` implements `renderStatic()` with
 * `CompileWithRenderStatic` here and `render()` on `main` - a rendering test does not see
 * the difference.
 *
 * The view is built from a rendering context rather than through `ViewFactoryInterface`,
 * which does not exist on TYPO3 v12.
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
        $renderingContext = $this->get(RenderingContextFactory::class)->create([
            'templateRootPaths' => [__DIR__ . '/../Fixtures/Templates/'],
        ]);
        $this->provideRequest($renderingContext, $this->extbaseRequest());

        $view = new TemplateView($renderingContext);
        $view->assignMultiple($variables);

        return trim($view->render($template));
    }

    /**
     * The form view helpers refuse to render without an Extbase request, see
     * `TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::getRequest()` - and
     * where they read it from changed with TYPO3 v13, which keeps the request of a
     * rendering context in its attributes.
     */
    private function provideRequest(RenderingContext $renderingContext, Request $request): void
    {
        if ((new Typo3Version())->getMajorVersion() >= 13) {
            $renderingContext->setAttribute(ServerRequestInterface::class, $request);
            return;
        }
        $renderingContext->setRequest($request);
    }

    private function extbaseRequest(): Request
    {
        return new Request(
            (new ServerRequest())
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
                ->withAttribute('extbase', new ExtbaseRequestParameters())
        );
    }
}
