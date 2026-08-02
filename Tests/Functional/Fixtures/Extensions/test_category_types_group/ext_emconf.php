<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Category Types Group',
    'description' => 'Extension registering a category type group for tests',
    'version' => '2.4.0',
    'category' => 'misc',
    'state' => 'beta',
    'author' => 'Stefan Bürk',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.22-13.4.99',
            'category_types' => '2.4.0',
        ],
    ],
];
