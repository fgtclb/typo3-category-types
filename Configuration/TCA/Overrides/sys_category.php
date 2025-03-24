<?php

declare(strict_types=1);

use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

(static function (): void {
    $items = [
        [
            'label' => 'LLL:EXT:category_types/Resources/Private/Language/locallang.xlf:sys_category.type.default',
            'value' => 'default',
            'icon' => 'mimetypes-x-sys_category',
        ],
    ];

    $categoryTypeRegistry = GeneralUtility::makeInstance(CategoryTypeRegistry::class);
    $categoryTypes = $categoryTypeRegistry->getCategoryTypes();
    $typeIconClasses = [];

    foreach ($categoryTypes as $categoryType) {
        $items[] = [
            'label' => $categoryType->getTitle(),
            'value' => $categoryType->getIdentifier(),
            'icon' => $categoryType->getIconIdentifier(),
            'group' => $categoryType->getGroup(),
        ];
        $typeIconClasses[$categoryType->getIdentifier()] = $categoryType->getIconIdentifier();
    }

    $sysCategoryTca = [
        'ctrl' => [
            'type' => 'type',
            'typeicon_classes' => [],
            'typeicon_column' => 'type',
        ],
        'columns' => [
            'type' => [
                'label' => 'LLL:EXT:category_types/Resources/Private/Language/locallang.xlf:sys_category.type',
                'config' => [
                    'default' => 'default',
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => $items,
                ],
            ],
        ],
    ];

    \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
        $GLOBALS['TCA']['sys_category'],
        $sysCategoryTca
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'sys_category',
        'type',
        '',
        'before:title'
    );

    $GLOBALS['TCA']['sys_category']['ctrl']['typeicon_classes'] = $typeIconClasses;
})();
