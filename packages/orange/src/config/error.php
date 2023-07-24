<?php

declare(strict_types=1);

return [
    'view paths' => [],
    'types' => [
        'cli' => [
            'subfolder' => 'cli',
            'mime type' => 'text/plain',
            'charset' => 'utf-8',
        ],
        'ajax' => [
            'subfolder' => 'ajax',
            'mime type' => 'application/json',
            'charset' => 'utf-8',
        ],
        'html' => [
            'subfolder' => 'html',
            'mime type' => 'text/html',
            'charset' => 'utf-8',
        ],
    ],
    // default - this is overridden by the input class on instantiation
    'request type' => 'html',
    'default error view' => 'error',
    'default status code' => 500,
    'default key' => 'default',
];
