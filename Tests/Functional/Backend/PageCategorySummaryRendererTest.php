<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\Backend;

use FGTCLB\CategoryTypes\Backend\PageCategorySummaryRenderer;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use FGTCLB\CategoryTypes\Tests\Functional\AbstractCategoryTypesTestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

/**
 * `Backend\PageCategorySummaryRenderer` produces the table `EXT:academic_programs`,
 * `EXT:academic_projects` and `EXT:academic_partners` add above the content grid of the page
 * module. These tests render it for the `testing` group of the fixture extension, so they
 * describe the renderer rather than the category set of one of those extensions.
 *
 * The page type of the fixture is `199`, a number no extension of this repository claims. The
 * renderer compares whatever it is handed against the `doktype` of the page record and has no
 * opinion about the value, and a fixture that says so is harder to misread than one reusing
 * the `20` of a program page.
 */
final class PageCategorySummaryRendererTest extends AbstractCategoryTypesTestCase
{
    private const GROUP = 'testing';
    private const PAGE_TYPE = 199;
    private const SUMMARISED_PAGE = 2;
    private const PAGE_WITHOUT_CATEGORIES = 3;
    private const STANDARD_PAGE = 4;

    protected function setUp(): void
    {
        $this->addTestExtension('tests/category-types-group');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/PageCategorySummary/summaryPages.csv');
        $this->setUpBackendUser(1);
    }

    /**
     * The defect this renderer replaces: the three partials translated
     * `sys_category.academic_<ext>.{type}`, a key that exists in no XLF file of any of them,
     * so every type label of the summary would have been empty had a template rendered them.
     * The titles asserted here are the ones the types are registered with.
     */
    #[Test]
    public function everyTypeOfTheGroupIsListedWithItsRegisteredTitle(): void
    {
        $summary = $this->renderSummaryOf(self::SUMMARISED_PAGE);

        $this->assertStringContainsString('Testing first', $summary);
        $this->assertStringContainsString('Testing second', $summary);
    }

    #[Test]
    public function theCategoriesOfThePageAreListedBelowTheirType(): void
    {
        $summary = $this->renderSummaryOf(self::SUMMARISED_PAGE);

        $this->assertStringContainsString('First Category', $summary);
        $this->assertStringContainsString('Second Category', $summary);
    }

    /**
     * A category that is switched off is assigned all the same, and the page properties are
     * the only other place an editor could notice it. It keeps its row and trades the type
     * icon for the core hidden overlay.
     *
     * The count is what makes this a test of the condition rather than of the icon: the
     * fixture page carries three categories and exactly one of them is hidden, so a condition
     * that is always true renders three overlays and a condition that is never true renders
     * none. Both are red here, and only the discriminating one is green.
     */
    #[Test]
    public function aHiddenCategoryIsListedAndMarkedAndTheOthersAreNot(): void
    {
        $summary = $this->renderSummaryOf(self::SUMMARISED_PAGE);

        $this->assertStringContainsString('Hidden Category', $summary);
        $this->assertSame(1, substr_count($summary, 'data-identifier="overlay-hidden"'));
        $this->assertSame(
            2,
            substr_count($summary, 'data-identifier="category_types.testing.testing_first"'),
            'the type row and the one visible category of that type',
        );
    }

    /**
     * `CategoryCollection::getAllCategoriesByType()` answers with every type of the group and
     * an empty list for the ones the page carries nothing of, which is what turns into this
     * note. A summary that listed only the assigned types would leave an editor guessing
     * whether a type exists at all.
     */
    #[Test]
    public function aTypeWithoutACategoryIsListedWithTheNotSetNote(): void
    {
        $summary = $this->renderSummaryOf(self::PAGE_WITHOUT_CATEGORIES);

        $this->assertStringContainsString('Testing first', $summary);
        $this->assertStringContainsString('Testing second', $summary);
        $this->assertStringContainsString('Not set', $summary);
    }

    /**
     * The guard the three listeners rely on. The fixture page carries two categories of the
     * group, so an empty answer here is the page type being rejected and not an empty result
     * set.
     */
    #[Test]
    public function aPageOfAnotherTypeGetsNoSummaryAlthoughItCarriesCategories(): void
    {
        $this->assertSame('', $this->renderSummaryOf(self::STANDARD_PAGE));
    }

    /**
     * A guard, not a proof, and measured as one: removing the `$pageId === 0` early return
     * from the renderer leaves this test green. With no page there is no record to compare,
     * so the doktype comparison rejects the request anyway - and neither core version
     * dispatches the event for page 0 to begin with. It is kept because
     * `renderForPageOfType()` is public API that something other than the page module may
     * call, and it pins the answer for that case.
     */
    #[Test]
    public function aRequestWithoutAPageGetsNoSummary(): void
    {
        $summary = $this->renderer()->renderForPageOfType(
            $this->pageModuleRequest(0),
            self::PAGE_TYPE,
            self::GROUP,
        );

        $this->assertSame('', $summary);
    }

    /**
     * An installation can switch off the extension that registers the group while the one
     * asking for the summary stays active. `CategoryTypeRegistry::getCategoryTypesByGroup()`
     * raises an `\InvalidArgumentException` for a group it does not know, and a page module
     * that dies is a worse answer than one without a summary.
     */
    #[Test]
    public function anUnregisteredGroupGetsNoSummaryInsteadOfAnException(): void
    {
        $summary = $this->renderer()->renderForPageOfType(
            $this->pageModuleRequest(self::SUMMARISED_PAGE),
            self::PAGE_TYPE,
            'a-group-no-extension-registers',
        );

        $this->assertSame('', $summary);
    }

    private function renderSummaryOf(int $pageId): string
    {
        return $this->renderer()->renderForPageOfType(
            $this->pageModuleRequest($pageId),
            self::PAGE_TYPE,
            self::GROUP,
        );
    }

    /**
     * The shape of the request the page module dispatches its event with: a backend request
     * carrying the page as `id`. `BackendViewFactory::create()` reads that parameter itself to
     * resolve the page TSconfig the template override is registered in, which is why the
     * renderer reads the page from the same place rather than from an argument of its own.
     */
    private function pageModuleRequest(int $pageId): ServerRequestInterface
    {
        $request = (new ServerRequest('https://localhost/typo3/module/web/layout'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withQueryParams(['id' => (string)$pageId]);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        return $request;
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
