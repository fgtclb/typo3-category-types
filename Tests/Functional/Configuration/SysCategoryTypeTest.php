<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\Configuration;

use FGTCLB\CategoryTypes\Tests\Functional\AbstractCategoryTypesTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\Processor\SelectItemProcessor;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

/**
 * Covers the `sys_category` type select that `Configuration/TCA/Overrides/sys_category.php`
 * builds from the registry, as far as the developer page on `CategoryTypes.yaml` describes
 * it: a category stores the identifier of its type without the group, and the title the
 * `groups:` section of the file declares heads the types of the group.
 *
 * The fixture extension `test_category_types_group` declares its group with the title
 * `Testing` in that section, and two types in it. `test_category_types_undeclared_group`
 * has a type in the group `news`, which no package declares, followed by a type in the
 * group `late`, which the same file declares with the title `Declared late`.
 */
final class SysCategoryTypeTest extends AbstractCategoryTypesTestCase
{
    protected function setUp(): void
    {
        $this->addTestExtension('tests/category-types-group', 'tests/category-types-undeclared-group');
        parent::setUp();
    }

    #[Test]
    public function typeSelectStoresTheIdentifierWithoutTheGroup(): void
    {
        $this->assertSame(
            [
                'label' => 'Testing first',
                'value' => 'testing_first',
                'icon' => 'category_types.testing.testing_first',
                'group' => 'testing',
            ],
            $this->typeItem('testing_first'),
        );
    }

    #[Test]
    public function typeIconIsKeyedByTheIdentifierWithoutTheGroup(): void
    {
        $typeIconClasses = $GLOBALS['TCA']['sys_category']['ctrl']['typeicon_classes'];

        $this->assertSame('category_types.testing.testing_first', $typeIconClasses['testing_first'] ?? null);
        $this->assertArrayNotHasKey('testing.testing_first', $typeIconClasses);
    }

    /**
     * The declared title is registered as the item group label, so FormEngine heads the
     * types of the group with it rather than with the key. The divider is what an editor
     * sees above the types of the group.
     */
    #[Test]
    public function typeSelectHeadsTheGroupWithItsDeclaredTitle(): void
    {
        $this->assertSame('Testing', $this->typeSelectConfig()['itemGroups']['testing'] ?? null);
        $this->assertSame(['Testing'], $this->groupHeadings('testing'));
    }

    /**
     * A group no package declares gets no item group label, and FormEngine falls back to
     * its key.
     */
    #[Test]
    public function typeSelectHeadsAnUndeclaredGroupWithItsKey(): void
    {
        $this->assertArrayNotHasKey('news', $this->typeSelectConfig()['itemGroups'] ?? []);
        $this->assertSame(['news'], $this->groupHeadings('news'));
    }

    /**
     * FormEngine lists the groups of `itemGroups` first and appends the groups it only finds
     * in the items. The fixture lists the type of `news` before the type of `late`, so the
     * titled group `late` moving ahead of `news` comes from the item group labels alone.
     */
    #[Test]
    public function groupWithATitleIsHeadedBeforeAGroupWithout(): void
    {
        $values = array_column($this->typeSelectConfig()['items'], 'value');
        $this->assertLessThan(
            array_search('testing_late', $values, true),
            array_search('testing_news', $values, true),
        );

        $headings = array_merge($this->groupHeadings('late'), $this->groupHeadings('news'));
        $this->assertSame(['Declared late', 'news'], $headings);
        $this->assertLessThan(
            $this->headingPosition('news'),
            $this->headingPosition('late'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function typeSelectConfig(): array
    {
        return $GLOBALS['TCA']['sys_category']['columns']['type']['config'];
    }

    /**
     * @return array<int, array<string, mixed>> The items as FormEngine renders them, with a
     *                                          divider above the types of each group.
     */
    private function renderedItems(): array
    {
        // The item group labels are translated with the language of the backend user.
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $config = $this->typeSelectConfig();

        return array_values($this->get(SelectItemProcessor::class)->groupAndSortItems(
            $config['items'],
            $config['itemGroups'] ?? [],
            [],
        ));
    }

    /**
     * @return string[] The labels of the dividers FormEngine puts above the types of the group.
     */
    private function groupHeadings(string $group): array
    {
        return array_column(
            array_filter(
                $this->renderedItems(),
                static fn(array $item): bool => ($item['value'] ?? null) === '--div--' && ($item['group'] ?? null) === $group,
            ),
            'label',
        );
    }

    private function headingPosition(string $group): int
    {
        foreach ($this->renderedItems() as $position => $item) {
            if (($item['value'] ?? null) === '--div--' && ($item['group'] ?? null) === $group) {
                return $position;
            }
        }
        $this->fail(sprintf('No heading for the group "%s".', $group));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function typeItem(string $value): ?array
    {
        foreach ($GLOBALS['TCA']['sys_category']['columns']['type']['config']['items'] as $item) {
            if (($item['value'] ?? null) === $value) {
                return $item;
            }
        }
        return null;
    }
}
