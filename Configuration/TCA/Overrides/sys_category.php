<?php

declare(strict_types=1);

(static function (): void {
    $llBackend = function (string $label) {
        return sprintf('LLL:EXT:fgtclb_educational/Resources/Private/Language/locallang.xlf:sys_category.%s', $label);
    };
    $llBackendType = function (string $label) {
        return sprintf('LLL:EXT:fgtclb_educational/Resources/Private/Language/locallang.xlf:sys_category.type.%s', $label);
    };
    $sysCategoryTca = [
        'ctrl' => [
            'type' => 'type',
            'typeicon_classes' => [
            ],
            'typeicon_column' => 'type',
        ],
        'columns' => [
            'type' => [
                'label' => $llBackend('type'),
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        [
                            $llBackendType('default'),
                            '',
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
