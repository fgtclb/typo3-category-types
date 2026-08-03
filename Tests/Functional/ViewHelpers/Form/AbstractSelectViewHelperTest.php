<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\ViewHelpers\Form;

use FGTCLB\CategoryTypes\Tests\Functional\ViewHelpers\AbstractViewHelperTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * `ViewHelpers\Form\AbstractSelectViewHelper` renders an empty select on its own - its
 * `getOptions()` returns `[]` - so everything it contributes around the options is asserted
 * through `FilterSelectViewHelperTest`, which uses the subclass shipped here.
 *
 * What that leaves is `renderOptionTags()` and `renderOptionTag()`, which every subclass
 * shares: `ViewHelpers\Form\FilterSelectViewHelper` and the three `SortingSelectViewHelper`
 * implementations of `EXT:academic_partners`, `EXT:academic_programs` and
 * `EXT:academic_projects` only fill `getOptions()` and let the base class write the markup.
 * The `TestSelectViewHelper` of the `test_category_types_group` fixture extension is built
 * the same way and stands in for them, so the contract stays covered inside this extension.
 */
final class AbstractSelectViewHelperTest extends AbstractViewHelperTestCase
{
    protected function setUp(): void
    {
        $this->addTestExtension(...array_values([
            'tests/category-types-group',
        ]));
        parent::setUp();
    }

    #[Test]
    public function optionsAreRenderedInTheGivenOrder(): void
    {
        $output = $this->renderTestSelect(['options' => ['title' => 'By title', 'date' => 'By date']]);

        $this->assertSame(
            '<select name="sorting">'
            . '<option value="title">By title</option>' . LF
            . '<option value="date">By date</option>' . LF
            . '</select>',
            $output,
        );
    }

    #[Test]
    public function selectedOptionIsMarked(): void
    {
        $output = $this->renderTestSelect([
            'value' => 'date',
            'options' => ['title' => 'By title', 'date' => 'By date'],
        ]);

        $this->assertStringContainsString('<option value="title">By title</option>', $output);
        $this->assertStringContainsString('<option value="date" selected="selected">By date</option>', $output);
    }

    #[Test]
    public function optionLabelIsEscaped(): void
    {
        $output = $this->renderTestSelect(['options' => ['title' => 'Fluid & "Templating" <b>']]);

        $this->assertStringContainsString(
            '<option value="title">Fluid &amp; &quot;Templating&quot; &lt;b&gt;</option>',
            $output,
        );
    }

    /**
     * The label was escaped and the value was not, although both are written by the same
     * method. A value reaches the markup unchanged from `getOptions()`, which a subclass
     * fills - for the category filter from a property the template names.
     */
    #[Test]
    public function optionValueIsEscaped(): void
    {
        $output = $this->renderTestSelect(['options' => ['" autofocus onfocus="alert(1)' => 'By title']]);

        $this->assertStringContainsString(
            '<option value="&quot; autofocus onfocus=&quot;alert(1)">By title</option>',
            $output,
        );
    }

    #[Test]
    public function withoutOptionsAnEmptySelectIsRendered(): void
    {
        $this->assertSame('<select name="sorting"></select>', $this->renderTestSelect());
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function renderTestSelect(array $variables = []): string
    {
        return $this->render('TestSelect', array_replace(
            [
                'name' => 'sorting',
                'value' => '',
                'options' => [],
            ],
            $variables,
        ));
    }
}
