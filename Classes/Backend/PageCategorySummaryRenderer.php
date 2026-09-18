<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Backend;

use FGTCLB\CategoryTypes\Domain\Model\Category;
use FGTCLB\CategoryTypes\Domain\Model\CategoryType;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Renders the category summary an extension adds above the content grid of the page module,
 * for a page type of its own.
 *
 * It lives here and not in the three extensions that show it, because the summary is the same
 * table for a program, a project and a partner page and differs only in the category group.
 * It shipped three times before, as `PageLayout/Doktype{20,30,40}.html` of
 * `EXT:academic_programs`, `EXT:academic_projects` and `EXT:academic_partners`, and so did the
 * two defects those partials carried: they were registered as an override of a core backend
 * partial that no core template renders, and they translated a label key that exists in no
 * XLF file.
 *
 * Resolving the page belongs here for the same reason. The three listeners of the extensions
 * would otherwise carry one copy each of the same ten lines, which is how the defect got
 * shipped three times in the first place; what stays with the extension is its two constants,
 * the page type and the category group.
 */
final readonly class PageCategorySummaryRenderer
{
    private const TEMPLATE = 'PageCategorySummary';

    /**
     * The package the view resolves its template from. Naming it explicitly is what gives an
     * integrator the override key `templates.fgtclb/category-types.<key>` in page TSconfig,
     * see {@see BackendViewFactory::create()}; it also keeps the factory from asking the
     * request for its `route` attribute, which it only does when no package is named.
     */
    private const PACKAGE = 'fgtclb/category-types';

    public function __construct(
        private BackendViewFactory $backendViewFactory,
        private CategoryRepository $categoryRepository,
        private CategoryTypeRegistry $categoryTypeRegistry,
        private LanguageServiceFactory $languageServiceFactory,
    ) {}

    /**
     * The summary for the page the backend request addresses, or an empty string when that
     * page is not of `$doktype` - which is every page of the installation but the ones the
     * calling extension owns.
     *
     * An empty string is the answer to everything that is not this listener's page,
     * rather than an exception or a flash message: the page module of a standard page
     * has nothing to say about the categories of another page type, and
     * `ModifyPageLayoutContentEvent::addHeaderContent()` appends one without a trace.
     *
     * The same answer covers a page the backend user may not read and a category group
     * no active extension registers.
     */
    public function renderForPageOfType(ServerRequestInterface $request, int $doktype, string $group): string
    {
        // A short-circuit, not a rule: neither core version dispatches this event for
        // page 0, and without it the doktype comparison below would reject the request
        // anyway. It saves the record lookup for a caller that is not the page module.
        $pageId = $this->pageIdOf($request);
        if ($pageId === 0) {
            return '';
        }

        $backendUser = $GLOBALS['BE_USER'] ?? null;
        if (!$backendUser instanceof BackendUserAuthentication) {
            return '';
        }

        // `readPageAccess()` answers `false` for a page the user may not see, and the summary
        // is not the place to tell them it exists. Its signature differs between the supported
        // versions - v13 declares no return type, v14 declares `array|false` - so the result is
        // checked with `is_array()` rather than against `false`.
        $pageRecord = BackendUtility::readPageAccess(
            $pageId,
            $backendUser->getPagePermsClause(Permission::PAGE_SHOW)
        );
        if (!is_array($pageRecord) || (int)($pageRecord['doktype'] ?? 0) !== $doktype) {
            return '';
        }

        return $this->render($request, $pageId, $group);
    }

    private function render(ServerRequestInterface $request, int $pageId, string $group): string
    {
        // An installation can deactivate the extension that registers the group while the one
        // rendering the summary stays active. `getCategoryTypesByGroup()` raises for an
        // unknown group, and a page module that dies is a worse answer than one without a
        // summary.
        $types = $this->categoryTypeRegistry->getGroupedCategoryTypes()[$group] ?? [];
        if ($types === []) {
            return '';
        }

        // Hidden categories are asked for on purpose: an editor has to see a category that is
        // switched off, which is the one thing the page properties would not show them either.
        $categoriesByType = $this->categoryRepository
            ->findByGroupAndPageId($group, $pageId, true)
            ->getAllCategoriesByType();

        $view = $this->backendViewFactory->create($request, [self::PACKAGE]);
        $view->assignMultiple([
            'group' => $group,
            'pageId' => $pageId,
            'rows' => $this->rows($types, $categoriesByType),
        ]);

        return $view->render(self::TEMPLATE);
    }

    /**
     * One row per registered type of the group, in registry order, carrying the type itself
     * for a template override to reach and the two values the shipped template needs.
     *
     * The title is resolved here rather than with `f:translate` in the template, because a
     * category type does not have to carry a label reference: `CategoryTypes.yaml` takes a
     * literal title just as well, and one project uses that. `LanguageService::sL()` resolves
     * an `LLL:` reference and returns anything else unchanged, where `f:translate` answers an
     * empty string for the literal - which is the shape of the defect this change repairs.
     *
     * @param CategoryType[] $types
     * @param array<string, Category[]> $categoriesByType
     * @return list<array{type: CategoryType, title: string, iconIdentifier: string, categories: Category[]}>
     */
    private function rows(array $types, array $categoriesByType): array
    {
        $languageService = $this->languageServiceFactory->createFromUserPreferences(
            $GLOBALS['BE_USER'] ?? null
        );

        $rows = [];
        foreach ($types as $type) {
            $rows[] = [
                'type' => $type,
                'title' => $languageService->sL($type->getTitle()),
                'iconIdentifier' => $type->getIconIdentifier(),
                'categories' => $categoriesByType[$type->getIdentifier()] ?? [],
            ];
        }

        return $rows;
    }

    /**
     * The page the request addresses, read the way {@see BackendViewFactory::create()} reads
     * it - so the page the summary is built for is the page whose TSconfig decided which
     * template renders it.
     */
    private function pageIdOf(ServerRequestInterface $request): int
    {
        $parsedBody = $request->getParsedBody();
        $pageId = (is_array($parsedBody) ? $parsedBody['id'] ?? null : null)
            ?? $request->getQueryParams()['id']
            ?? 0;

        return MathUtility::canBeInterpretedAsInteger($pageId) ? (int)$pageId : 0;
    }
}
