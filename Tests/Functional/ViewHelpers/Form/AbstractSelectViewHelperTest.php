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
 * What that leaves is `renderOptionTags()`, which the filter select overrides and therefore
 * never reaches. The three `SortingSelectViewHelper` implementations of
 * `EXT:academic_partners`, `EXT:academic_programs` and `EXT:academic_projects` do use it:
 * they only fill `getOptions()` and let the base class write the markup. The
 * `TestSelectViewHelper` of the `test_category_types_group` fixture extension is built the
 * same way and stands in for them, so the contract stays covered inside this extension.
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
