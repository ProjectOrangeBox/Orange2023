<?php

declare(strict_types=1);

return [
    // append view folder
    'add path' => __DIR__ . '/../views',

    // default service setup should override this
    'request type' => 'html',

    // prefix views with
    'default root folder' => 'errors',

    // default view
    'defaultView' => 'error',

    'deduplicate' => true,

    'types' => [
        'cli' => [
            'mime type' => 'text/plain',
            'charset' => 'utf-8',
            'folder' => 'errors/cli',
        ],
        'ajax' => [
            'mime type' => 'application/json',
            'charset' => 'utf-8',
            'folder' => 'errors/ajax',
        ],
        'html' => [
            'mime type' => 'text/html',
            'charset' => 'utf-8',
            'folder' => 'errors/html',
        ],
    ],
];
