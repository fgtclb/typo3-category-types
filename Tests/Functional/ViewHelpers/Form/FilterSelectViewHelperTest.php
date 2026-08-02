<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\ViewHelpers\Form;

use FGTCLB\CategoryTypes\Domain\Model\Category;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use FGTCLB\CategoryTypes\Tests\Functional\ViewHelpers\AbstractViewHelperTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * `ViewHelpers\Form\FilterSelectViewHelper` renders the category filter of the list plugins
 * of `EXT:academic_partners`, `EXT:academic_programs` and `EXT:academic_projects` - their
 * `DemandCategories.html` partials are its only callers.
 *
 * Everything `ViewHelpers\Form\AbstractSelectViewHelper` contributes - the name, the
 * prepended option, `required`, the child content, the `renderOptions` variable and the
 * selected-value handling - is asserted here rather than in a test of its own: the class is
 * not abstract, but on its own it renders an empty select, because its `getOptions()`
 * returns `[]`. Its own `renderOptionTags()`, which the filter select overrides, is covered
 * in `AbstractSelectViewHelperTest` - the three `SortingSelectViewHelper` implementations of
 * `EXT:academic_partners`, `EXT:academic_programs` and `EXT:academic_projects` are its other
 * subclasses and use it.
 *
 * The options are built as `Category` objects instead of being read from the database, which
 * pins the order the assertions rely on: none of the repository methods sorts. `isRoot()`
 * still queries, so the parents come from the `categoryTree.csv` fixture.
 */
final class FilterSelectViewHelperTest extends AbstractViewHelperTestCase
{
    protected function setUp(): void
    {
        $this->addTestExtension(...array_values([
            'tests/category-types-group',
        ]));
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/CategoryRepository/categoryTree.csv');
    }

    #[Test]
    public function everyCategoryBecomesAnOption(): void
    {
        $output = $this->renderFilterSelect(['options' => [
            $this->category(1, 0, 'Root Category'),
            $this->category(6, 0, 'Second Type Root', 'testing_second'),
        ]]);

        $this->assertSame(
            '<select class="form-select" name="filter[researchField]">'
            . '<option value="1" class="level-0">Root Category</option>' . LF
            . '<option value="6" class="level-0">Second Type Root</option>' . LF
            . '</select>',
            $output,
        );
    }

    #[Test]
    public function optionsAreReadFromACategoryCollection(): void
    {
        // The production callers hand over the collection of a repository method, not an
        // array - `getOptions()` accepts both.
        $options = $this->get(CategoryRepository::class)->findByGroupAndUidList('testing', [1, 2]);

        $output = $this->renderFilterSelect(['options' => $options]);

        $this->assertStringContainsString('<option value="1" class="level-0">Root Category</option>', $output);
        $this->assertStringContainsString('<option value="2" class="level-0">Child Category</option>', $output);
    }

    #[Test]
    public function optionLabelIsEscaped(): void
    {
        $output = $this->renderFilterSelect(['options' => [
            $this->category(1, 0, 'Fluid & "Templating" <b>'),
        ]]);

        $this->assertStringContainsString(
            '<option value="1" class="level-0">Fluid &amp; &quot;Templating&quot; &lt;b&gt;</option>',
            $output,
        );
    }

    #[Test]
    public function withoutOptionsAnEmptySelectIsRendered(): void
    {
        $this->assertSame(
            '<select class="form-select" name="filter[researchField]"></select>',
            $this->renderFilterSelect(),
        );
    }

    #[Test]
    public function selectedValueIsMarked(): void
    {
        $output = $this->renderFilterSelect([
            'value' => '2',
            'options' => [$this->category(1, 0, 'Root Category'), $this->category(2, 1, 'Child Category')],
        ]);

        $this->assertStringContainsString('<option value="1" class="level-0">Root Category</option>', $output);
        $this->assertStringContainsString(
            '<option value="2" class="level-0" selected="selected">Child Category</option>',
            $output,
        );
    }

    #[Test]
    public function severalValuesCanBeSelected(): void
    {
        $output = $this->renderFilterSelect([
            'value' => ['1', '2'],
            'options' => [
                $this->category(1, 0, 'Root Category'),
                $this->category(2, 1, 'Child Category'),
                $this->category(3, 2, 'Grandchild Category'),
            ],
        ]);

        $this->assertStringContainsString('<option value="1" class="level-0" selected="selected">', $output);
        $this->assertStringContainsString('<option value="2" class="level-0" selected="selected">', $output);
        $this->assertStringContainsString('<option value="3" class="level-0">Grandchild Category</option>', $output);
    }

    /**
     * A category as the value is what `{demand.filterCollection.filterCategories.<type>}`
     * hands over. `Category` is no Extbase entity, so the persistence manager has no
     * identifier for it and the cast falls back to `__toString()`, which returns the uid.
     */
    #[Test]
    public function selectedValueCanBeACategory(): void
    {
        $output = $this->renderFilterSelect([
            'value' => $this->category(2, 1, 'Child Category'),
            'options' => [$this->category(1, 0, 'Root Category'), $this->category(2, 1, 'Child Category')],
        ]);

        $this->assertStringContainsString(
            '<option value="2" class="level-0" selected="selected">Child Category</option>',
            $output,
        );
    }

    /**
     * `optionValueField` is not a registered argument of this view helper, it arrives through
     * the additional-argument handling of the tag based base class - and the partials of the
     * three consuming extensions pass it. It still reaches `getOptionValueScalar()`.
     */
    #[Test]
    public function selectedValueCanBeReadFromAPropertyOfTheValueObject(): void
    {
        $output = $this->renderFilterSelect(
            [
                'value' => $this->category(2, 1, 'Child Category'),
                'options' => [$this->category(2, 1, 'Child Category')],
            ],
            'FilterSelectWithOptionValueField',
        );

        $this->assertStringContainsString('selected="selected"', $output);
    }

    #[Test]
    public function valueThatCannotBeCastToAStringIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1742820065);

        $this->renderFilterSelect(['value' => new \stdClass()]);
    }

    #[Test]
    public function disabledCategoryIsRenderedAsADisabledOption(): void
    {
        $disabled = $this->category(2, 1, 'Child Category');
        $disabled->setDisabled(true);

        $output = $this->renderFilterSelect(['options' => [$this->category(1, 0, 'Root Category'), $disabled]]);

        $this->assertStringContainsString('<option value="1" class="level-0">Root Category</option>', $output);
        $this->assertStringContainsString(
            '<option value="2" class="level-0" disabled="disabled">Child Category</option>',
            $output,
        );
    }

    #[Test]
    public function prependedOptionComesFirst(): void
    {
        $output = $this->render('FilterSelectWithPrependedOption', [
            'name' => 'filter[researchField]',
            'options' => [$this->category(1, 0, 'Root Category')],
            'prependOptionLabel' => 'All categories',
            'prependOptionValue' => '',
        ]);

        $this->assertSame(
            '<select class="form-select" name="filter[researchField]">'
            . '<option value="">All categories</option>' . LF
            . '<option value="1" class="level-0">Root Category</option>' . LF
            . '</select>',
            $output,
        );
    }

    #[Test]
    public function prependedOptionCanCarryAValue(): void
    {
        $output = $this->render('FilterSelectWithPrependedOption', [
            'name' => 'filter[researchField]',
            'options' => [],
            'prependOptionLabel' => 'All categories',
            'prependOptionValue' => 'all',
        ]);

        $this->assertStringContainsString('<option value="all">All categories</option>', $output);
    }

    /**
     * The label is what decides whether the entry is rendered, and Fluid counts an empty
     * string as a given argument - so a `prependOptionLabel` whose translation is missing
     * adds an empty entry rather than none.
     */
    #[Test]
    public function prependedOptionWithoutALabelStillAddsAnEntry(): void
    {
        $output = $this->render('FilterSelectWithPrependedOption', [
            'name' => 'filter[researchField]',
            'options' => [],
            'prependOptionLabel' => '',
            'prependOptionValue' => '',
        ]);

        $this->assertStringContainsString('<option value=""></option>', $output);
    }

    #[Test]
    public function optionsAreSortedByLabelOnDemand(): void
    {
        $output = $this->renderFilterSelect([
            'options' => [
                $this->category(1, 0, 'Zeta'),
                $this->category(6, 0, 'Alpha', 'testing_second'),
            ],
            'sortByOptionLabel' => true,
        ]);

        $this->assertSame(
            '<select class="form-select" name="filter[researchField]">'
            . '<option value="6" class="level-0">Alpha</option>' . LF
            . '<option value="1" class="level-0">Zeta</option>' . LF
            . '</select>',
            $output,
        );
    }

    /**
     * Grouping does not nest the markup - it reorders the flat list so children follow their
     * parent, and records the depth in a class name the stylesheet indents on.
     */
    #[Test]
    public function optionsAreOrderedByTheirParentOnDemand(): void
    {
        $output = $this->renderFilterSelect([
            'options' => [
                $this->category(3, 2, 'Grandchild Category'),
                $this->category(2, 1, 'Child Category'),
                $this->category(1, 0, 'Root Category'),
            ],
            'groupByParent' => true,
        ]);

        $this->assertSame(
            '<select class="form-select" name="filter[researchField]">'
            . '<option value="1" class="level-0">Root Category</option>' . LF
            . '<option value="2" class="level-1">Child Category</option>' . LF
            . '<option value="3" class="level-2">Grandchild Category</option>' . LF
            . '</select>',
            $output,
        );
    }

    /**
     * A category whose parent carries a different type is a root of its own, because the
     * select only ever shows one type - see `Tests/Functional/Domain/Model/CategoryTest`.
     */
    #[Test]
    public function categoryBelowAParentOfAnotherTypeIsGroupedAsARoot(): void
    {
        $output = $this->renderFilterSelect([
            'options' => [$this->category(7, 6, 'Child Of Another Type')],
            'groupByParent' => true,
        ]);

        $this->assertStringContainsString('<option value="7" class="level-0">', $output);
    }

    /**
     * Grouping walks down from the roots, so an option whose root is not part of the list is
     * dropped rather than rendered without a parent. The three consuming extensions always
     * pass a whole category type, where that cannot happen.
     */
    #[Test]
    public function groupedOptionWithoutItsRootIsDropped(): void
    {
        $output = $this->renderFilterSelect([
            'options' => [$this->category(2, 1, 'Child Category'), $this->category(3, 2, 'Grandchild Category')],
            'groupByParent' => true,
        ]);

        $this->assertSame('<select class="form-select" name="filter[researchField]"></select>', $output);
    }

    #[Test]
    public function levelClassPrefixCanBeChanged(): void
    {
        $output = $this->renderFilterSelect([
            'options' => [$this->category(2, 1, 'Child Category'), $this->category(1, 0, 'Root Category')],
            'groupByParent' => true,
            'groupLevelClassPrefix' => 'depth-',
        ]);

        $this->assertStringContainsString('<option value="1" class="depth-0">', $output);
        $this->assertStringContainsString('<option value="2" class="depth-1">', $output);
    }

    #[Test]
    public function requiredIsRenderedAsAnAttribute(): void
    {
        $output = $this->renderFilterSelect(['required' => true]);

        $this->assertStringContainsString('required="required"', $output);
    }

    #[Test]
    public function tagContentOfTheTemplateIsKept(): void
    {
        $output = $this->render('FilterSelectWithContent', [
            'name' => 'filter[researchField]',
            'options' => [$this->category(1, 0, 'Root Category')],
            'optionsAfterContent' => false,
        ]);

        $this->assertSame(
            '<select name="filter[researchField]">'
            . '<option value="1" class="level-0">Root Category</option>' . LF
            . '<option value="own">Own option</option>'
            . '</select>',
            $output,
        );
    }

    #[Test]
    public function tagContentCanBePlacedBeforeTheGeneratedOptions(): void
    {
        $output = $this->render('FilterSelectWithContent', [
            'name' => 'filter[researchField]',
            'options' => [$this->category(1, 0, 'Root Category')],
            'optionsAfterContent' => true,
        ]);

        $this->assertSame(
            '<select name="filter[researchField]">'
            . '<option value="own">Own option</option>'
            . '<option value="1" class="level-0">Root Category</option>' . LF
            . '</select>',
            $output,
        );
    }

    /**
     * With `renderOptions="false"` the prepared options are handed to the template as a
     * variable named `options` instead of being rendered, which lets a project template mark
     * up the entries itself. The variable is removed again afterwards.
     */
    #[Test]
    public function optionsCanBeRenderedByTheTemplateItself(): void
    {
        $output = $this->render('FilterSelectWithoutRenderedOptions', [
            'name' => 'filter[researchField]',
            'options' => [$this->category(1, 0, 'Root Category'), $this->category(2, 1, 'Child Category')],
        ]);

        $this->assertSame(
            '<select name="filter[researchField]">[1:Root Category][2:Child Category]</select>',
            $output,
        );
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function renderFilterSelect(array $variables = [], string $template = 'FilterSelect'): string
    {
        return $this->render($template, array_replace(
            [
                'name' => 'filter[researchField]',
                'value' => '',
                'options' => [],
                'required' => false,
                'sortByOptionLabel' => false,
                'groupByParent' => false,
                'groupLevelClassPrefix' => 'level-',
            ],
            $variables,
        ));
    }

    private function category(int $uid, int $parentId, string $title, string $type = 'testing_first'): Category
    {
        return new Category(
            uid: $uid,
            parentId: $parentId,
            title: $title,
            type: $type,
            typeGroup: 'testing',
        );
    }
}
