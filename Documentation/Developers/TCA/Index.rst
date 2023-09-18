.. include:: /Includes.rst.txt

TCA Implementation
==================

..  code-block:: php
    :caption: EXT:example/Configuration/TCA/Overrides/sys_category.php

    (static function (): void {
        $llBackendType = function (string $label) {
            return sprintf('LLL:EXT:example/Resources/Private/Language/locallang.xlf:sys_category.type.%s', $label);
        };

        // Optional, use your own flavour
        $iconType = function (string $iconType) {
            return sprintf(
                'academic-studies-%s',
                $iconType
            );
        };
    
        $sysCategoryTcaTypeIconOverrides = [
            'ctrl' => [
                'typeicon_classes' => [
                    \FGTCLB\Example\Domain\Enumeration\Category::TYPE_ADMISSION_RESTRICTION
                    => $iconType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_ADMISSION_RESTRICTION),
                    \FGTCLB\Example\Domain\Enumeration\Category::TYPE_APPLICATION_PERIOD
                    => $iconType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_APPLICATION_PERIOD),
                    \FGTCLB\Example\Domain\Enumeration\Category::TYPE_BEGIN_COURSE
                    => $iconType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_BEGIN_COURSE),
                    \FGTCLB\Example\Domain\Enumeration\Category::TYPE_COSTS
                    => $iconType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_COSTS),
                    \FGTCLB\Example\Domain\Enumeration\Category::TYPE_DEGREE
                    => $iconType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_DEGREE),
                    \FGTCLB\Example\Domain\Enumeration\Category::TYPE_DEPARTMENT
                    => $iconType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_DEPARTMENT),
                    \FGTCLB\Example\Domain\Enumeration\Category::TYPE_STANDARD_PERIOD
                    => $iconType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_STANDARD_PERIOD),
                    \FGTCLB\Example\Domain\Enumeration\Category::TYPE_COURSE_TYPE
                    => $iconType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_COURSE_TYPE),
                    \FGTCLB\Example\Domain\Enumeration\Category::TYPE_TEACHING_LANGUAGE
                    => $iconType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_TEACHING_LANGUAGE),
                    \FGTCLB\Example\Domain\Enumeration\Category::TYPE_TOPIC
                    => $iconType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_TOPIC),
                ],
            ],
        ];
        $addItems = [
            [
                $llBackendType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_ADMISSION_RESTRICTION),
                \FGTCLB\Example\Domain\Enumeration\Category::TYPE_ADMISSION_RESTRICTION,
                $iconType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_ADMISSION_RESTRICTION),
                'courses',
            ],
            [
                $llBackendType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_APPLICATION_PERIOD),
                \FGTCLB\Example\Domain\Enumeration\Category::TYPE_APPLICATION_PERIOD,
                $iconType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_APPLICATION_PERIOD),
                'courses',
            ],
            [
                $llBackendType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_BEGIN_COURSE),
                \FGTCLB\Example\Domain\Enumeration\Category::TYPE_BEGIN_COURSE,
                $iconType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_BEGIN_COURSE),
                'courses',
            ],
            [
                $llBackendType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_COSTS),
                \FGTCLB\Example\Domain\Enumeration\Category::TYPE_COSTS,
                $iconType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_COSTS),
                'courses',
            ],
            [
                $llBackendType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_DEGREE),
                \FGTCLB\Example\Domain\Enumeration\Category::TYPE_DEGREE,
                $iconType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_DEGREE),
                'courses',
            ],
            [
                $llBackendType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_DEPARTMENT),
                \FGTCLB\Example\Domain\Enumeration\Category::TYPE_DEPARTMENT,
                $iconType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_DEPARTMENT),
                'courses',
            ],
            [
                $llBackendType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_STANDARD_PERIOD),
                \FGTCLB\Example\Domain\Enumeration\Category::TYPE_STANDARD_PERIOD,
                $iconType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_STANDARD_PERIOD),
                'courses',
            ],
            [
                $llBackendType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_COURSE_TYPE),
                \FGTCLB\Example\Domain\Enumeration\Category::TYPE_COURSE_TYPE,
                $iconType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_COURSE_TYPE),
                'courses',
            ],
            [
                $llBackendType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_TEACHING_LANGUAGE),
                \FGTCLB\Example\Domain\Enumeration\Category::TYPE_TEACHING_LANGUAGE,
                $iconType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_TEACHING_LANGUAGE),
                'courses',
            ],
            [
                $llBackendType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_TOPIC),
                \FGTCLB\Example\Domain\Enumeration\Category::TYPE_TOPIC,
                $iconType(\FGTCLB\Example\Domain\Enumeration\Category::TYPE_TOPIC),
                'courses',
            ],
        ];
    
        // create new group
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItemGroup(
            'sys_category',
            'type',
            'courses',
            'LLL:EXT:example/Resources/Private/Language/locallang.xlf:sys_category.courses',
        );

        // add the items to group
        foreach ($addItems as $addItem) {
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
                'sys_category',
                'type',
                $addItem
            );
        }

        // add configuration to TCA
        \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
            $GLOBALS['TCA']['sys_category'],
            $sysCategoryTcaTypeIconOverrides
        );
    })();
