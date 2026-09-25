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
 * returns `[]`. Its `renderOptionTags()` writes the markup for this class as well and is
 * covered in `AbstractSelectViewHelperTest` - the three `SortingSelectViewHelper`
 * implementations of `EXT:academic_partners`, `EXT:academic_programs` and
 * `EXT:academic_projects` are its other subclasses and share it too.
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

    /**
     * A category title is free text an editor types, and `optionValueField` may name any
     * property since the four select arguments were registered - so the value is as
     * uncontrolled as the label, which was escaped from the start.
     */
    #[Test]
    public function optionValueIsEscaped(): void
    {
        $output = $this->renderWithOptionFields([
            'options' => [$this->category(1, 0, '" autofocus onfocus="alert(1)')],
            'optionValueField' => 'title',
        ]);

        $this->assertStringContainsString(
            '<option value="&quot; autofocus onfocus=&quot;alert(1)" class="level-0">',
            $output,
        );
    }

    #[Test]
    public function levelClassPrefixIsEscaped(): void
    {
        $output = $this->renderFilterSelect([
            'options' => [$this->category(1, 0, 'Root Category')],
            'groupLevelClassPrefix' => '" autofocus onfocus="alert(1)',
        ]);

        $this->assertStringContainsString(
            '<option value="1" class="&quot; autofocus onfocus=&quot;alert(1)0">',
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
     * `{demand.filterCollection.filterCategories.<type>}` hands over a list of categories,
     * and every element goes through the same cast - so a single one covers it. `Category`
     * is no Extbase entity, so the persistence manager has no identifier for it and the cast
     * falls back to `__toString()`, which returns the uid.
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
     * `optionValueField` decides how an object handed over as the value is turned into the
     * string the options are compared against - the case the three `DemandCategories.html`
     * partials pass it for.
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

    /**
     * A disabled category can be the selected one, and both attributes are written by the
     * same option. `disabled` comes from the option array now and therefore precedes
     * `selected`, which the base class appends last.
     */
    #[Test]
    public function disabledCategoryCanBeTheSelectedOne(): void
    {
        $disabled = $this->category(2, 1, 'Child Category');
        $disabled->setDisabled(true);

        $output = $this->renderFilterSelect(['value' => '2', 'options' => [$disabled]]);

        $this->assertStringContainsString(
            '<option value="2" class="level-0" disabled="disabled" selected="selected">Child Category</option>',
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
     * The three `DemandCategories.html` partials pass both fields. Before they were
     * registered they reached the markup as attributes of the `select` element, where
     * neither of them is valid HTML.
     */
    #[Test]
    public function optionFieldsAreNotRenderedAsAttributes(): void
    {
        $output = $this->renderWithOptionFields(['options' => [$this->category(1, 0, 'Root Category')]]);

        $this->assertSame(
            '<select name="filter[researchField]">'
            . '<option value="1" class="level-0">Root Category</option>' . LF
            . '</select>',
            $output,
        );
    }

    #[Test]
    public function optionLabelIsReadFromTheConfiguredField(): void
    {
        $output = $this->renderWithOptionFields([
            'options' => [$this->category(1, 0, 'Root Category')],
            'optionLabelField' => 'type',
        ]);

        $this->assertStringContainsString('<option value="1" class="level-0">testing_first</option>', $output);
    }

    #[Test]
    public function optionValueIsReadFromTheConfiguredField(): void
    {
        $output = $this->renderWithOptionFields([
            'options' => [$this->category(2, 1, 'Child Category')],
            'optionValueField' => 'parentId',
        ]);

        $this->assertStringContainsString('<option value="1" class="level-0">Child Category</option>', $output);
    }

    /**
     * The value the selection is compared against comes from the same field, so a value
     * field other than the uid stays consistent between option and selection.
     */
    #[Test]
    public function selectionIsComparedAgainstTheConfiguredValueField(): void
    {
        $output = $this->renderWithOptionFields([
            'value' => '1',
            'options' => [$this->category(2, 1, 'Child Category'), $this->category(3, 2, 'Grandchild Category')],
            'optionValueField' => 'parentId',
        ]);

        $this->assertStringContainsString(
            '<option value="1" class="level-0" selected="selected">Child Category</option>',
            $output,
        );
        $this->assertStringContainsString('<option value="2" class="level-0">Grandchild Category</option>', $output);
    }

    /**
     * Every property a `Category` exposes is a scalar or `Stringable`, so the rejection
     * needs an option of its own kind - which is what an `options` argument holding
     * anything but categories amounts to.
     */
    #[Test]
    public function optionFieldThatCannotBeCastToAStringIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1785706600);

        $this->renderWithOptionFields(['options' => [new class () {
            public function getUid(): int
            {
                return 1;
            }

            /**
             * @return array<int, string>
             */
            public function getTitle(): array
            {
                return ['not', 'a', 'string'];
            }
        }]]);
    }

    /**
     * `multiple` is a valid attribute of a `select`, so before it was registered a template
     * passing it got a real multi select - but the name kept its single-value shape and the
     * browser submitted one of the selected options. The name carries the suffix now, the
     * way the core select view helper builds it, and the hidden field lets an empty
     * selection reach the controller.
     */
    #[Test]
    public function multipleSelectSubmitsAnArray(): void
    {
        $output = $this->renderMultiple(['options' => [$this->category(1, 0, 'Root Category')]]);

        $this->assertSame(
            '<input type="hidden" name="filter[researchField]" value="" />'
            . '<select multiple="multiple" name="filter[researchField][]">'
            . '<option value="1" class="level-0">Root Category</option>' . LF
            . '</select>',
            $output,
        );
    }

    #[Test]
    public function singleSelectKeepsItsName(): void
    {
        $output = $this->renderMultiple(['multiple' => false]);

        $this->assertStringContainsString('<select name="filter[researchField]">', $output);
    }

    #[Test]
    public function everyOptionOfAMultipleSelectIsSelectedByDefaultOnDemand(): void
    {
        $output = $this->renderMultiple([
            'options' => [$this->category(1, 0, 'Root Category'), $this->category(2, 1, 'Child Category')],
            'selectAllByDefault' => true,
        ]);

        $this->assertStringContainsString('<option value="1" class="level-0" selected="selected">', $output);
        $this->assertStringContainsString('<option value="2" class="level-0" selected="selected">', $output);
    }

    /**
     * A selection replaces the default - "if none was set before" is what the argument
     * promises.
     */
    #[Test]
    public function selectAllByDefaultStepsBackForASelection(): void
    {
        $output = $this->renderMultiple([
            'value' => ['2'],
            'options' => [$this->category(1, 0, 'Root Category'), $this->category(2, 1, 'Child Category')],
            'selectAllByDefault' => true,
        ]);

        $this->assertStringContainsString('<option value="1" class="level-0">Root Category</option>', $output);
        $this->assertStringContainsString(
            '<option value="2" class="level-0" selected="selected">Child Category</option>',
            $output,
        );
    }

    #[Test]
    public function selectAllByDefaultIsIgnoredForASingleSelect(): void
    {
        $output = $this->renderMultiple([
            'options' => [$this->category(1, 0, 'Root Category')],
            'multiple' => false,
            'selectAllByDefault' => true,
        ]);

        $this->assertStringContainsString('<option value="1" class="level-0">Root Category</option>', $output);
    }

    /**
     * `CategoryRepository::findAllApplicable()` disables every category no entry of the list
     * carries. With `hideDisabledOptions` those are left out instead of being rendered
     * disabled - and the argument is not rendered as an attribute of the `select` element,
     * which is what an undeclared argument of a tag-based view helper becomes.
     */
    #[Test]
    public function disabledOptionIsLeftOutOnDemand(): void
    {
        $output = $this->renderHidingDisabledOptions([
            'options' => [$this->category(1, 0, 'Root Category'), $this->disabledCategory(2, 1, 'Child Category')],
        ]);

        $this->assertSame(
            '<select name="filter[researchField]">'
            . '<option value="1" class="level-0">Root Category</option>' . LF
            . '</select>',
            $output,
        );
    }

    #[Test]
    public function disabledOptionIsKeptWhenHidingIsSwitchedOff(): void
    {
        $output = $this->renderHidingDisabledOptions([
            'options' => [$this->category(1, 0, 'Root Category'), $this->disabledCategory(2, 1, 'Child Category')],
            'hideDisabledOptions' => false,
        ]);

        $this->assertStringContainsString(
            '<option value="2" class="level-0" disabled="disabled">Child Category</option>',
            $output,
        );
    }

    /**
     * The active filter stays visible, so the visitor sees what is selected and can change it.
     */
    #[Test]
    public function disabledOptionIsKeptWhileItIsSelected(): void
    {
        $output = $this->renderHidingDisabledOptions([
            'value' => '2',
            'options' => [$this->category(1, 0, 'Root Category'), $this->disabledCategory(2, 1, 'Child Category')],
        ]);

        $this->assertStringContainsString(
            '<option value="2" class="level-0" disabled="disabled" selected="selected">Child Category</option>',
            $output,
        );
    }

    #[Test]
    public function disabledOptionIsKeptWhileItIsOneOfSeveralSelected(): void
    {
        $output = $this->renderHidingDisabledOptions([
            'value' => ['1', '2'],
            'options' => [
                $this->category(1, 0, 'Root Category'),
                $this->disabledCategory(2, 1, 'Child Category'),
                $this->disabledCategory(3, 2, 'Grandchild Category'),
            ],
            'multiple' => true,
        ]);

        $this->assertStringContainsString('<option value="2" class="level-0" disabled="disabled" selected="selected">', $output);
        $this->assertStringNotContainsString('value="3"', $output);
    }

    /**
     * `selectAllByDefault` marks every option selected while nothing is - that is no active
     * filter to keep visible, and a disabled option is never submitted anyway.
     */
    #[Test]
    public function optionSelectedOnlyByDefaultIsNotKept(): void
    {
        $output = $this->renderHidingDisabledOptions([
            'options' => [$this->category(1, 0, 'Root Category'), $this->disabledCategory(2, 1, 'Child Category')],
            'multiple' => true,
            'selectAllByDefault' => true,
        ]);

        $this->assertStringContainsString('<option value="1" class="level-0" selected="selected">', $output);
        $this->assertStringNotContainsString('value="2"', $output);
    }

    /**
     * Grouped, a parent without results stays while one of its descendants is shown, so the
     * hierarchy the level classes indent stays intact. A grandchild that is left out does not
     * keep its parent.
     */
    #[Test]
    public function disabledParentOfAShownChildIsKeptWhenGrouped(): void
    {
        $output = $this->renderHidingDisabledOptions([
            'options' => [
                $this->disabledCategory(1, 0, 'Root Category'),
                $this->category(2, 1, 'Child Category'),
                $this->disabledCategory(3, 2, 'Grandchild Category'),
            ],
            'groupByParent' => true,
        ]);

        $this->assertSame(
            '<select name="filter[researchField]">'
            . '<option value="1" class="level-0" disabled="disabled">Root Category</option>' . LF
            . '<option value="2" class="level-1">Child Category</option>' . LF
            . '</select>',
            $output,
        );
    }

    #[Test]
    public function disabledAncestorsOfAShownGrandchildAreKeptWhenGrouped(): void
    {
        $output = $this->renderHidingDisabledOptions([
            'options' => [
                $this->disabledCategory(1, 0, 'Root Category'),
                $this->disabledCategory(2, 1, 'Child Category'),
                $this->category(3, 2, 'Grandchild Category'),
            ],
            'groupByParent' => true,
        ]);

        $this->assertSame(
            '<select name="filter[researchField]">'
            . '<option value="1" class="level-0" disabled="disabled">Root Category</option>' . LF
            . '<option value="2" class="level-1" disabled="disabled">Child Category</option>' . LF
            . '<option value="3" class="level-2">Grandchild Category</option>' . LF
            . '</select>',
            $output,
        );
    }

    #[Test]
    public function disabledParentOfASelectedChildIsKeptWhenGrouped(): void
    {
        $output = $this->renderHidingDisabledOptions([
            'value' => '2',
            'options' => [$this->disabledCategory(1, 0, 'Root Category'), $this->disabledCategory(2, 1, 'Child Category')],
            'groupByParent' => true,
        ]);

        $this->assertStringContainsString('<option value="1" class="level-0" disabled="disabled">', $output);
        $this->assertStringContainsString('<option value="2" class="level-1" disabled="disabled" selected="selected">', $output);
    }

    /**
     * The option that follows a left-out branch is its sibling, not its descendant, and does
     * not keep it.
     */
    #[Test]
    public function disabledBranchWithoutAShownOptionIsLeftOutWhenGrouped(): void
    {
        $output = $this->renderHidingDisabledOptions([
            'options' => [
                $this->disabledCategory(1, 0, 'Root Category'),
                $this->disabledCategory(2, 1, 'Child Category'),
                $this->category(6, 0, 'Second Type Root', 'testing_second'),
            ],
            'groupByParent' => true,
        ]);

        $this->assertSame(
            '<select name="filter[researchField]">'
            . '<option value="6" class="level-0">Second Type Root</option>' . LF
            . '</select>',
            $output,
        );
    }

    /**
     * Ungrouped, every option is level 0 and none is below another: a parent without results
     * is left out even when a child of it is shown.
     */
    #[Test]
    public function disabledParentIsLeftOutWhenNotGrouped(): void
    {
        $output = $this->renderHidingDisabledOptions([
            'options' => [$this->disabledCategory(1, 0, 'Root Category'), $this->category(2, 1, 'Child Category')],
        ]);

        $this->assertSame(
            '<select name="filter[researchField]">'
            . '<option value="2" class="level-0">Child Category</option>' . LF
            . '</select>',
            $output,
        );
    }

    /**
     * A template that renders the options itself gets the same list the view helper would
     * render.
     */
    #[Test]
    public function optionsVariableLacksTheLeftOutOptions(): void
    {
        $output = $this->render('FilterSelectWithoutRenderedOptionsHidingDisabledOptions', [
            'name' => 'filter[researchField]',
            'options' => [$this->category(1, 0, 'Root Category'), $this->disabledCategory(2, 1, 'Child Category')],
        ]);

        $this->assertSame('<select name="filter[researchField]">[1:Root Category]</select>', $output);
    }

    /**
     * The call the `DemandCategories.html` partials make: `optionValueField="uid"`, the
     * selection handed over as the list of categories `FilterCollection` returns, and a
     * prepended "all" option.
     */
    #[Test]
    public function optionsAreLeftOutTheWayTheListPartialsCallTheField(): void
    {
        $output = $this->render('FilterSelectHidingDisabledOptionsLikeTheListPartials', [
            'name' => 'filter[researchField]',
            'value' => [$this->disabledCategory(2, 1, 'Child Category')],
            'options' => [
                $this->category(1, 0, 'Root Category'),
                $this->disabledCategory(2, 1, 'Child Category'),
                $this->disabledCategory(3, 2, 'Grandchild Category'),
            ],
        ]);

        $this->assertSame(
            '<select name="filter[researchField]">'
            . '<option value="">All categories</option>' . LF
            . '<option value="1" class="level-0">Root Category</option>' . LF
            . '<option value="2" class="level-0" disabled="disabled" selected="selected">Child Category</option>' . LF
            . '</select>',
            $output,
        );
    }

    /**
     * The prepended option is not one of the options, so a filter whose options are all
     * without results still offers the way back to every result.
     */
    #[Test]
    public function prependedOptionStaysWhenEveryOptionIsLeftOut(): void
    {
        $output = $this->render('FilterSelectHidingDisabledOptionsLikeTheListPartials', [
            'name' => 'filter[researchField]',
            'value' => '',
            'options' => [$this->disabledCategory(1, 0, 'Root Category')],
        ]);

        $this->assertSame(
            '<select name="filter[researchField]"><option value="">All categories</option>' . LF . '</select>',
            $output,
        );
    }

    /**
     * An option is kept by the same comparison that marks it selected, so no option is
     * rendered as selected in one place and left out as unselected in the other.
     */
    #[Test]
    public function selectionIsComparedTheWayTheSelectedMarkerCompares(): void
    {
        $output = $this->renderHidingDisabledOptions([
            'value' => '02',
            'options' => [$this->category(1, 0, 'Root Category'), $this->disabledCategory(2, 1, 'Child Category')],
        ]);

        $this->assertStringContainsString(
            '<option value="2" class="level-0" disabled="disabled" selected="selected">Child Category</option>',
            $output,
        );
    }

    /**
     * Sorting reorders the siblings before they are grouped; the kept parent still precedes
     * its child, and a sorted root without anything shown below it is left out.
     */
    #[Test]
    public function sortedAndGroupedOptionsKeepTheParentOfAShownChild(): void
    {
        $output = $this->renderHidingDisabledOptions([
            'options' => [
                $this->disabledCategory(6, 0, 'Second Type Root', 'testing_second'),
                $this->category(2, 1, 'Child Category'),
                $this->disabledCategory(1, 0, 'Root Category'),
            ],
            'sortByOptionLabel' => true,
            'groupByParent' => true,
        ]);

        $this->assertSame(
            '<select name="filter[researchField]">'
            . '<option value="1" class="level-0" disabled="disabled">Root Category</option>' . LF
            . '<option value="2" class="level-1">Child Category</option>' . LF
            . '</select>',
            $output,
        );
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function renderHidingDisabledOptions(array $variables = []): string
    {
        return $this->render('FilterSelectHidingDisabledOptions', array_replace(
            [
                'name' => 'filter[researchField]',
                'value' => '',
                'options' => [],
                'sortByOptionLabel' => false,
                'groupByParent' => false,
                'multiple' => false,
                'selectAllByDefault' => false,
                'hideDisabledOptions' => true,
            ],
            $variables,
        ));
    }

    private function disabledCategory(int $uid, int $parentId, string $title, string $type = 'testing_first'): Category
    {
        $category = $this->category($uid, $parentId, $title, $type);
        $category->setDisabled(true);
        return $category;
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function renderWithOptionFields(array $variables = []): string
    {
        return $this->render('FilterSelectWithOptionFields', array_replace(
            [
                'name' => 'filter[researchField]',
                'value' => '',
                'options' => [],
                'optionValueField' => 'uid',
                'optionLabelField' => 'title',
            ],
            $variables,
        ));
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function renderMultiple(array $variables = []): string
    {
        return $this->render('FilterSelectMultiple', array_replace(
            [
                'name' => 'filter[researchField]',
                'value' => '',
                'options' => [],
                'multiple' => true,
                'selectAllByDefault' => false,
            ],
            $variables,
        ));
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
