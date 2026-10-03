<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\ViewHelpers;

use FGTCLB\CategoryTypes\Tests\Functional\AbstractCategoryTypesTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

/**
 * `ViewHelpers\CategoryTypeTitleViewHelper` names a category type by the title it is
 * registered with, which is what the frontend shows for a type its extension has no
 * label for.
 *
 * The fixture extension `test_category_types_titles` registers `funding` with the literal
 * title "Funding & grants" and `teaching_form` with a label reference, "Form of teaching",
 * "Lehrform" in German. The template brackets the value, so an empty title is visible as
 * `[]`.
 *
 * It is a functional test and not a unit test because the title is resolved through the
 * language files of the installation.
 */
final class CategoryTypeTitleViewHelperTest extends AbstractCategoryTypesTestCase
{
    protected function setUp(): void
    {
        $this->addTestExtension('tests/category-types-titles');
        parent::setUp();
    }

    #[Test]
    public function aLiteralTitleIsShownAsWritten(): void
    {
        $this->assertSame('[Funding &amp; grants]', $this->render('CategoryTypeTitle', 'titles', 'funding'));
    }

    #[Test]
    public function aTitleReferenceIsTranslated(): void
    {
        $this->assertSame('[Form of teaching]', $this->render('CategoryTypeTitle', 'titles', 'teaching_form'));
    }

    #[Test]
    public function aTitleReferenceIsTranslatedIntoTheLanguageOfThePage(): void
    {
        $this->assertSame('[Lehrform]', $this->render('CategoryTypeTitle', 'titles', 'teaching_form', 'de_DE.UTF-8'));
    }

    #[Test]
    public function aLiteralTitleIsTheSameInEveryLanguage(): void
    {
        $this->assertSame('[Funding &amp; grants]', $this->render('CategoryTypeTitle', 'titles', 'funding', 'de_DE.UTF-8'));
    }

    #[Test]
    public function anUnknownTypeHasAnEmptyTitle(): void
    {
        $this->assertSame('[]', $this->render('CategoryTypeTitle', 'titles', 'unknown'));
    }

    #[Test]
    public function aTypeOfAnotherGroupIsUnknown(): void
    {
        $this->assertSame('[]', $this->render('CategoryTypeTitle', 'programs', 'teaching_form'));
    }

    /**
     * The way the templates of the academic extensions use it: after the label lookup,
     * which finds no label for the type. The title is escaped once.
     */
    #[Test]
    public function afterAMissingLabelTheTitleIsShownAndEscapedOnce(): void
    {
        $this->assertSame('<b>Funding &amp; grants:</b>', $this->render('CategoryTypeTitleAfterMissingLabel', 'titles', 'funding'));
    }

    #[Test]
    public function afterAMissingLabelTheTitleIsTranslated(): void
    {
        $this->assertSame('<b>Lehrform:</b>', $this->render('CategoryTypeTitleAfterMissingLabel', 'titles', 'teaching_form', 'de_DE.UTF-8'));
    }

    /**
     * A label, of the extension or of the site, wins over the title, and is escaped once.
     */
    #[Test]
    public function aLabelWinsOverTheTitle(): void
    {
        $this->assertSame('<b>Grants &amp; loans:</b>', $this->render('CategoryTypeTitleAfterLabel', 'titles', 'funding', label: 'Grants & loans'));
    }

    #[Test]
    public function anEmptyLabelFallsBackToTheTitle(): void
    {
        $this->assertSame('<b>Form of teaching:</b>', $this->render('CategoryTypeTitleAfterLabel', 'titles', 'teaching_form', label: ''));
    }

    /**
     * Without a site language, as in a command, the title is resolved in the default
     * language.
     */
    #[Test]
    public function withoutASiteLanguageATitleReferenceIsResolvedInTheDefaultLanguage(): void
    {
        $this->assertSame('[Form of teaching]', $this->render('CategoryTypeTitle', 'titles', 'teaching_form', null));
    }

    /**
     * A frontend request carries a site language, and `f:translate` of TYPO3 v13 fails in a
     * frontend request without one.
     */
    private function render(string $template, string $group, string $identifier, ?string $locale = 'en_US.UTF-8', string $label = ''): string
    {
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        if ($locale !== null) {
            $request = $request->withAttribute('language', new SiteLanguage(0, $locale, new Uri('/'), []));
        }
        $view = $this->get(ViewFactoryInterface::class)->create(new ViewFactoryData(
            templateRootPaths: [__DIR__ . '/../Fixtures/Templates/'],
            request: $request,
        ));
        $view->assignMultiple(['group' => $group, 'identifier' => $identifier, 'label' => $label]);

        return trim($view->render($template));
    }
}
