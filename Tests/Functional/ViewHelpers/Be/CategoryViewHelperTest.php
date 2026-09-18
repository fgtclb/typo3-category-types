<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\ViewHelpers\Be;

use FGTCLB\CategoryTypes\Tests\Functional\ViewHelpers\AbstractViewHelperTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * `ViewHelpers\Be\CategoryViewHelper` provides the categories of a page to a backend template.
 *
 * It has **no caller in this repository** any more. Its only ones were the
 * `PageLayout/Doktype*.html` partials of `EXT:academic_partners`,
 * `EXT:academic_programs` and `EXT:academic_projects`, which ACE-688, ACE-689 and
 * ACE-690 removed. None of the three was ever rendered by a core template, so nothing
 * entered the view helper through them: the `EXT:academic_programs` one stopped being
 * rendered when a rename turned it into that partial in March 2023, and the other two
 * were created from the already broken shape and never rendered at all. The page module
 * summary that replaced them reads the categories through
 * `Domain\Repository\CategoryRepository` directly.
 *
 * The view helper stays public API and stays covered: a project may render it in a backend
 * template of its own, and these tests are what that contract rests on.
 *
 * The class differs slightly between the branches, and these tests describe both because
 * they go through a template rather than through the view helper's methods. Here the
 * `page` and `group` arguments carry no `defaultValue` and `render()` guards its
 * rendering context; on `2` they carry one and there is no guard. Fluid 5, shipped with
 * TYPO3 v14, rejects a required argument that is defined with a default, which is why the
 * two differ at all.
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
