<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Category Types Group',
    'description' => 'Extension registering a category type group for tests',
    'version' => '3.0.0',
    'category' => 'misc',
    'state' => 'beta',
    'author' => 'Stefan Bürk',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.3.99',
            'category_types' => '3.0.0',
        ],
    ],
];
