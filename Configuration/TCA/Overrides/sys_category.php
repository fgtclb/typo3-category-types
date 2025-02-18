<?php

declare(strict_types=1);

use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

(static function (): void {
    $ll = static fn (string $key): string => sprintf('LLL:EXT:category_types/Resources/Private/Language/locallang.xlf:%s', $key);

    $items = [
        [
            $ll('default'),
            'default',
            'mimetypes-x-sys_category',
        ],
    ];

    $categoryTypeRegistry = GeneralUtility::makeInstance(CategoryTypeRegistry::class);
    $categoryTypes = $categoryTypeRegistry->getCategoryTypes();
    $typeIconClasses = [];

    foreach ($categoryTypes as $categoryType) {
        $items[] = [
            $categoryType->getTitle(),
            $categoryType->getIdentifier(),
            $categoryType->getIconIdentifier(),
            $categoryType->getGroup(),
        ];
        $typeIconClasses[$categoryType->getIdentifier()] = $categoryType->getIconIdentifier();
    }

    $sysCategoryTca = [
        'ctrl' => [
            'type' => 'type',
            'typeicon_classes' => [
            ],
            'typeicon_column' => 'type',
        ],
        'columns' => [
            'type' => [
                'label' => $ll('type'),
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

    ArrayUtility::mergeRecursiveWithOverrule(
        $GLOBALS['TCA']['sys_category'],
        [
            'ctrl' => [
                'typeicon_classes' => $typeIconClasses,
            ],
        ]
    );
})();
