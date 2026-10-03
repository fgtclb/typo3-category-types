<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

return [
    'category_types.frontend.replaced' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_category_types_frontend_icons/Resources/Public/Icons/SiteReplaced.svg',
    ],
];
