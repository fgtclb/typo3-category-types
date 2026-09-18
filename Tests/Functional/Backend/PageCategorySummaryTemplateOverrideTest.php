<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\Backend;

use FGTCLB\CategoryTypes\Backend\PageCategorySummaryRenderer;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use FGTCLB\CategoryTypes\Tests\Functional\AbstractCategoryTypesTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

/**
 * The summary template is replaceable through page TSconfig, which is the whole point of
 * rendering it through `BackendViewFactory` instead of a partial of `EXT:backend`.
 *
 * The fixture extension registers
 * `templates.fgtclb/category-types.test-override` in its own `Configuration/page.tsconfig`,
 * which is what an installation writes into its site or page TSconfig, and ships the
 * replacement as `Resources/Private/Backend/Templates/PageCategorySummary.html`.
 *
 * This is a test of one line of `PageCategorySummaryRenderer`: the package name handed to
 * `BackendViewFactory::create()`. Naming `fgtclb/category-types` there is what puts this
 * extension's `Resources/Private/Templates/` on the search path and, with it, makes the
 * factory read `templates.fgtclb/category-types.*` from the page TSconfig. Named
 * `typo3/cms-backend` instead - the package the three removed partials registered against -
 * the view searches core's template directory and nothing else, and does not find the
 * shipped template either. That is the shape of the original defect, measured rather than
 * assumed.
 */
final class PageCategorySummaryTemplateOverrideTest extends AbstractCategoryTypesTestCase
{
    private const GROUP = 'testing';
    private const PAGE_TYPE = 199;
    private const SUMMARISED_PAGE = 2;

    protected function setUp(): void
    {
        $this->addTestExtension(
            'tests/category-types-group',
            'tests/category-types-summary-override',
        );
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/PageCategorySummary/summaryPages.csv');
        $this->setUpBackendUser(1);
    }

    #[Test]
    public function theRegisteredOverrideReplacesTheShippedTemplate(): void
    {
        $summary = $this->renderSummary();

        $this->assertStringContainsString('summary-from-the-override', $summary);
        $this->assertStringNotContainsString('category-types-page-summary', $summary);
    }

    /**
     * The override receives the same variables, so a project rewrites the markup without
     * reimplementing the query: two categories of `testing_first` - one of them hidden -
     * and one of `testing_second`.
     */
    #[Test]
    public function theOverrideIsHandedTheSameRows(): void
    {
        $this->assertStringContainsString(
            '[Testing first:2][Testing second:1]',
            $this->renderSummary(),
        );
    }

    private function renderSummary(): string
    {
        $request = (new ServerRequest('https://localhost/typo3/module/web/layout'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withQueryParams(['id' => (string)self::SUMMARISED_PAGE]);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        return $this->renderer()->renderForPageOfType(
            $request,
            self::PAGE_TYPE,
            self::GROUP,
        );
    }

    /**
     * The renderer is a private service: nothing but the three listeners of
     * `EXT:academic_programs`, `EXT:academic_projects` and `EXT:academic_partners` asks for
     * it, and they get it injected. Building it from its dependencies here keeps it that way
     * rather than widening the production container for a test - that the service is
     * autowirable at all is what the listener test of each extension proves, by dispatching
     * the event the page module dispatches.
     */
    private function renderer(): PageCategorySummaryRenderer
    {
        return new PageCategorySummaryRenderer(
            $this->get(BackendViewFactory::class),
            $this->get(CategoryRepository::class),
            $this->get(CategoryTypeRegistry::class),
            $this->get(LanguageServiceFactory::class),
        );
    }
}
