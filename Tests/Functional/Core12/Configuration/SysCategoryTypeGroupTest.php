<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\Core12\Configuration;

use FGTCLB\CategoryTypes\Tests\Functional\AbstractCategoryTypesTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems;

/**
 * The group heading of the `sys_category` type select on TYPO3 v12, where FormEngine groups
 * the items in the protected `TcaSelectItems::groupAndSortItems()`; v13 moved that into
 * `SelectItemProcessor`, see the `Core13/` sibling.
 *
 * The fixture extension `test_category_types_group` declares its group with the title
 * `Testing` in the `groups:` section of its `CategoryTypes.yaml`.
 */
final class SysCategoryTypeGroupTest extends AbstractCategoryTypesTestCase
{
    protected function setUp(): void
    {
        $this->addTestExtension('tests/category-types-group');
        parent::setUp();
    }

    /**
     * No item group label is registered, so FormEngine heads the types of the group with
     * the raw key `testing` - never with the title `Testing` the `groups:` section of the
     * fixture declares. The divider is what an editor sees above the types of the group.
     */
    #[Group('not-core-13')]
    #[Test]
    public function typeSelectHeadsTheGroupWithItsRawKey(): void
    {
        $config = $GLOBALS['TCA']['sys_category']['columns']['type']['config'];
        $this->assertArrayNotHasKey('testing', $config['itemGroups'] ?? []);

        $provider = new class () extends TcaSelectItems {
            /**
             * @param array<int, array<string, mixed>> $items
             * @param array<string, string> $itemGroups
             * @return array<int, array<string, mixed>>
             */
            public function grouped(array $items, array $itemGroups): array
            {
                return $this->groupAndSortItems($items, $itemGroups, []);
            }
        };
        $items = $provider->grouped($config['items'], $config['itemGroups'] ?? []);

        $dividers = array_values(array_filter(
            $items,
            static fn(array $item): bool => ($item['value'] ?? null) === '--div--' && ($item['group'] ?? null) === 'testing',
        ));
        $this->assertCount(1, $dividers);
        $this->assertSame('testing', $dividers[0]['label']);
    }
}
