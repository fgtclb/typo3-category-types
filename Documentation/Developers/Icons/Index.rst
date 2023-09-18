.. include:: /Includes.rst.txt

Icon registering
================

..  code-block:: php
    :caption: EXT:example/Configuration/Icons.php

    use FGTCLB\Example\Domain\Enumeration\Category;

    // be aware, your Icons are correct located and named
    $sourceString = function (string $icon) {
        return sprintf(
            'EXT:example/Resources/Public/Icons/%s.svg',
            \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToLowerCamelCase($icon)
        );
    };
    
    $identifierString = function (string $identifier) {
        return sprintf(
            'academic-studies-%s',
            $identifier
        );
    };
    
    return [
        $identifierString(Category::TYPE_ADMISSION_RESTRICTION) => [
            'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            'source' => $sourceString(Category::TYPE_ADMISSION_RESTRICTION),
        ],
        $identifierString(Category::TYPE_APPLICATION_PERIOD) => [
            'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            'source' => $sourceString(Category::TYPE_APPLICATION_PERIOD),
        ],
        $identifierString(Category::TYPE_BEGIN_COURSE) => [
            'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            'source' => $sourceString(Category::TYPE_BEGIN_COURSE),
        ],
        $identifierString(Category::TYPE_COSTS) => [
            'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            'source' => $sourceString(Category::TYPE_COSTS),
        ],
        $identifierString(Category::TYPE_DEGREE) => [
            'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            'source' => $sourceString(Category::TYPE_DEGREE),
        ],
        $identifierString(Category::TYPE_DEPARTMENT) => [
            'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            'source' => $sourceString(Category::TYPE_DEPARTMENT),
        ],
        $identifierString(Category::TYPE_STANDARD_PERIOD) => [
            'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            'source' => $sourceString(Category::TYPE_STANDARD_PERIOD),
        ],
        $identifierString(Category::TYPE_COURSE_TYPE) => [
            'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            'source' => $sourceString(Category::TYPE_COURSE_TYPE),
        ],
        $identifierString(Category::TYPE_TEACHING_LANGUAGE) => [
            'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            'source' => $sourceString(Category::TYPE_TEACHING_LANGUAGE),
        ],
        $identifierString(Category::TYPE_TOPIC) => [
            'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            'source' => $sourceString(Category::TYPE_TOPIC),
        ],
    ];
