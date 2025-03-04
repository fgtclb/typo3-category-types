<?php

declare(strict_types=1);

(static function (): void {
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
                    'items' => [
                        [
                            'LLL:EXT:category_types/Resources/Private/Language/locallang.xlf:sys_category.type.default',
                            'default',
                            'mimetypes-x-sys_category',
                        ],
                    ],
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
})();
