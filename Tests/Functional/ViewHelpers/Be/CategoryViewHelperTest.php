<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\ViewHelpers\Be;

use FGTCLB\CategoryTypes\Tests\Functional\ViewHelpers\AbstractViewHelperTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * `ViewHelpers\Be\CategoryViewHelper` provides the categories of a page to the backend page
 * layout of `EXT:academic_partners`, `EXT:academic_programs` and `EXT:academic_projects` -
 * their `PageLayout/Doktype*.html` partials are its only callers.
 *
 * It is the one class of this extension that differs between the branches: on `2` it renders
 * through `renderStatic()` and `CompileWithRenderStatic`, here through `render()`. These
 * tests go through a template and therefore describe both.
 */
final class CategoryViewHelperTest extends AbstractViewHelperTestCase
{
    private const CATEGORISED_PAGE = 2;
    private const UNCATEGORISED_PAGE = 4;

    protected function setUp(): void
    {
        $this->addTestExtension(...array_values([
            'tests/category-types-group',
        ]));
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/CategoryRepository/categorisedPages.csv');
    }

    #[Test]
    public function categoriesOfThePageAreProvidedGroupedByType(): void
    {
        $output = $this->render('BeCategory', ['page' => self::CATEGORISED_PAGE, 'group' => 'testing']);

        $this->assertSame(
            '[testing_first:First Category:Hidden Category][testing_second:Second Category]',
            $output,
        );
    }

    /**
     * The view helper always asks for hidden records: an editor has to see a category that is
     * switched off, which is what the plain `findByGroupAndPageId()` call would hide.
     */
    #[Test]
    public function hiddenCategoriesAreProvidedAsWell(): void
    {
        $output = $this->render('BeCategory', ['page' => self::CATEGORISED_PAGE, 'group' => 'testing']);

        $this->assertStringContainsString('Hidden Category', $output);
    }

    /**
     * Every type of the group gets an entry even when the page carries no category at all, so
     * the backend module renders its rows instead of nothing.
     */
    #[Test]
    public function pageWithoutCategoriesStillProvidesTheTypesOfTheGroup(): void
    {
        $output = $this->render('BeCategory', ['page' => self::UNCATEGORISED_PAGE, 'group' => 'testing']);

        $this->assertSame('[testing_first][testing_second]', $output);
    }

    #[Test]
    public function variableNameCanBeChosen(): void
    {
        $output = $this->render('BeCategoryWithAlias', ['page' => self::CATEGORISED_PAGE, 'group' => 'testing']);

        $this->assertSame('[First Category][Hidden Category]', $output);
    }

    #[Test]
    public function providedVariableIsRemovedAfterTheTag(): void
    {
        $output = $this->render('BeCategoryVariableScope', ['page' => self::CATEGORISED_PAGE, 'group' => 'testing']);

        $this->assertSame('inside:yes|outside:', $output);
    }

    #[Test]
    public function unknownGroupIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1683633304209);

        $this->render('BeCategory', ['page' => self::CATEGORISED_PAGE, 'group' => 'unknown']);
    }
}
