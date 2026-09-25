<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Unit\Backend\FormEngine;

use FGTCLB\CategoryTypes\Backend\FormEngine\CategoryTypeItemsProcFunc;
use FGTCLB\CategoryTypes\Domain\Model\CategoryType;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CategoryTypeItemsProcFuncTest extends UnitTestCase
{
    private function subject(): CategoryTypeItemsProcFunc
    {
        $registry = new CategoryTypeRegistry();
        $registry->attach(
            $this->categoryType('degree', 'programs', 'LLL:EXT:test_extension/locallang.xlf:degree'),
            $this->categoryType('region', 'partners', 'Region'),
            $this->categoryType('location', 'programs', 'Location'),
            $this->categoryType('costs', 'programs', 'Costs'),
        );

        return new CategoryTypeItemsProcFunc($registry);
    }

    private function categoryType(string $identifier, string $group, string $title): CategoryType
    {
        return new CategoryType(
            identifier: $identifier,
            extensionKey: 'test_extension',
            title: $title,
            group: $group,
            icon: 'EXT:test_extension/Resources/Public/Icons/' . $identifier . '.svg',
            priority: 0,
        );
    }

    /**
     * The title is handed over as it is registered: FormEngine translates the label of
     * every item after the provider ran, and a literal title has to reach the editor
     * unchanged.
     */
    #[Test]
    public function offersTheTypesOfTheGroupInRegistryOrder(): void
    {
        $params = [
            'items' => [],
            'config' => ['itemsProcConfig' => ['group' => 'programs']],
        ];

        $this->subject()->itemsForGroup($params);

        $this->assertSame(
            [
                ['label' => 'LLL:EXT:test_extension/locallang.xlf:degree', 'value' => 'degree', 'icon' => 'category_types.programs.degree'],
                ['label' => 'Location', 'value' => 'location', 'icon' => 'category_types.programs.location'],
                ['label' => 'Costs', 'value' => 'costs', 'icon' => 'category_types.programs.costs'],
            ],
            $params['items'],
        );
    }

    #[Test]
    public function keepsTheItemsTheFieldAlreadyHas(): void
    {
        $params = [
            'items' => [['label' => 'Static', 'value' => 'static']],
            'config' => ['itemsProcConfig' => ['group' => 'partners']],
        ];

        $this->subject()->itemsForGroup($params);

        $this->assertSame(
            [
                ['label' => 'Static', 'value' => 'static'],
                ['label' => 'Region', 'value' => 'region', 'icon' => 'category_types.partners.region'],
            ],
            $params['items'],
        );
    }

    /**
     * @return \Generator<string, array{0: array<string, mixed>}>
     */
    public static function configurationWithoutKnownGroupDataProvider(): \Generator
    {
        yield 'no itemsProcConfig' => [[]];
        yield 'no group' => [['itemsProcConfig' => []]];
        yield 'empty group' => [['itemsProcConfig' => ['group' => '']]];
        yield 'group not a string' => [['itemsProcConfig' => ['group' => ['programs']]]];
        yield 'unknown group' => [['itemsProcConfig' => ['group' => 'unknown']]];
    }

    /**
     * An installation can deactivate the extension that registers the group while the field
     * naming it stays configured. A form with an empty select is the better answer than one
     * that shows the editor an error for it.
     *
     * @param array<string, mixed> $config
     */
    #[DataProvider('configurationWithoutKnownGroupDataProvider')]
    #[Test]
    public function offersNothingWithoutAKnownGroup(array $config): void
    {
        $params = ['items' => [], 'config' => $config];

        $this->subject()->itemsForGroup($params);

        $this->assertSame([], $params['items']);
    }
}
