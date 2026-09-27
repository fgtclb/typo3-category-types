<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\Configuration;

use FGTCLB\CategoryTypes\Tests\Functional\AbstractCategoryTypesTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\Processor\SelectItemProcessor;

/**
 * Covers the `sys_category` type select that `Configuration/TCA/Overrides/sys_category.php`
 * builds from the registry, as far as the developer page on `CategoryTypes.yaml` describes
 * it: a category stores the identifier of its type without the group, and the `groups:`
 * section of the file labels nothing.
 *
 * The fixture extension `test_category_types_group` declares its group with the title
 * `Testing` in that section, and two types in it.
 */
final class SysCategoryTypeTest extends AbstractCategoryTypesTestCase
{
    protected function setUp(): void
    {
        $this->addTestExtension('tests/category-types-group');
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
     * No item group label is registered, so FormEngine heads the types of the group with
     * the raw key `testing` - never with the title `Testing` the `groups:` section of the
     * fixture declares. The divider is what an editor sees above the types of the group.
     */
    #[Test]
    public function typeSelectHeadsTheGroupWithItsRawKey(): void
    {
        $config = $GLOBALS['TCA']['sys_category']['columns']['type']['config'];
        $this->assertArrayNotHasKey('testing', $config['itemGroups'] ?? []);

        $items = $this->get(SelectItemProcessor::class)->groupAndSortItems(
            $config['items'],
            $config['itemGroups'] ?? [],
            [],
        );

        $dividers = array_values(array_filter(
            $items,
            static fn(array $item): bool => ($item['value'] ?? null) === '--div--' && ($item['group'] ?? null) === 'testing',
        ));
        $this->assertCount(1, $dividers);
        $this->assertSame('testing', $dividers[0]['label']);
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
