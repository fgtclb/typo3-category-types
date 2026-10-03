<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\ViewHelpers;

use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * The title a category type is registered with in `Configuration/CategoryTypes.yaml`, in
 * the language of the page: the label behind an `LLL:` reference, a literal title as it
 * is written. An unknown type has an empty title.
 *
 * The frontend names a type by the label `sys_category.<group>.<identifier>` of its
 * extension, which only knows the shipped types. The label is the content of this view
 * helper, and the title is rendered only when the content is empty, so a label of the
 * extension or of the site wins:
 *
 * ```html
 * <html xmlns:ct="http://typo3.org/ns/FGTCLB/CategoryTypes/ViewHelpers" data-namespace-typo3-fluid="true">
 *
 * {f:translate(key: 'sys_category.programs.{type}', extensionName: 'AcademicPrograms')
 *     -> ct:categoryTypeTitle(group: 'programs', identifier: type)}
 * ```
 *
 * The title is not passed as the `default` of `f:translate`, because Fluid evaluates an
 * argument before the view helper runs. The language service caches a resolved label for
 * the whole request by locale and reference, without the overrides of the site. On TYPO3
 * v13 that reference is the one `f:translate` reads, and the shipped titles of partners and
 * projects are that very reference: resolved first, the title would hide a `_LOCAL_LANG`
 * label of the site.
 *
 * The title is resolved with `LanguageService::sL()` and not with `f:translate`, which
 * answers an empty string for a literal title, see the page module category summary.
 */
final class CategoryTypeTitleViewHelper extends AbstractViewHelper
{
    /**
     * The content is the output of `f:translate`, which is escaped with the output of this
     * view helper, once.
     *
     * @var bool
     */
    protected $escapeChildren = false;

    public function __construct(
        private readonly CategoryTypeRegistry $categoryTypeRegistry,
        private readonly LanguageServiceFactory $languageServiceFactory,
    ) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('group', 'string', 'The group of the category type, for example "programs".', true);
        $this->registerArgument('identifier', 'string', 'The identifier of the category type.', true);
    }

    public function render(): string
    {
        $content = $this->renderChildren();
        $label = is_scalar($content) ? (string)$content : '';
        if ($label !== '') {
            return $label;
        }
        $categoryType = $this->categoryTypeRegistry->getCategoryType(
            (string)$this->arguments['group'],
            (string)$this->arguments['identifier'],
        );
        if ($categoryType === null || $categoryType->getTitle() === '') {
            return '';
        }

        $siteLanguage = $this->siteLanguage();
        $languageService = $siteLanguage !== null
            ? $this->languageServiceFactory->createFromSiteLanguage($siteLanguage)
            : $this->languageServiceFactory->create('default');

        return $languageService->sL($categoryType->getTitle());
    }

    /**
     * The language of the request the template is rendered for. Rendered outside a site, as
     * in a test or a command, the title is resolved in the default language.
     */
    private function siteLanguage(): ?SiteLanguage
    {
        if (!$this->renderingContext instanceof RenderingContextInterface
            || !$this->renderingContext->hasAttribute(ServerRequestInterface::class)
        ) {
            return null;
        }
        $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        if (!$request instanceof ServerRequestInterface) {
            return null;
        }
        $siteLanguage = $request->getAttribute('language');

        return $siteLanguage instanceof SiteLanguage ? $siteLanguage : null;
    }
}
