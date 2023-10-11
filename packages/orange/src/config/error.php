<?php

declare(strict_types=1);

return [
    'default views' => null,
    'types' => [
        'cli' => [
            'subfolder' => 'errors/cli',
            'mime type' => 'text/plain',
            'charset' => 'utf-8',
        ],
        'ajax' => [
            'subfolder' => 'errors/ajax',
            'mime type' => 'application/json',
            'charset' => 'utf-8',
        ],
        'html' => [
            'subfolder' => 'errors/html',
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
