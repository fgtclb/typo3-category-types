<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Functional\Configuration;

use FGTCLB\CategoryTypes\Tests\Functional\AbstractCategoryTypesTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Covers the `sys_category` type select that `Configuration/TCA/Overrides/sys_category.php`
 * builds from the registry, as far as the developer page on `CategoryTypes.yaml` describes
 * it: a category stores the identifier of its type without the group. That the `groups:`
 * section of the file labels nothing is covered per core version by
 * `Core12/Configuration/SysCategoryTypeGroupTest` and its `Core13/` sibling, because TYPO3
 * v12 groups the items in `TcaSelectItems` and v13 in `SelectItemProcessor`.
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
