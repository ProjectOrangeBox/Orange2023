<?php

declare(strict_types=1);

use orange\framework\Application;

return [
    'display_errors' => 0,
    'display_startup_errors' => 0,
    'error_reporting' => 0,

    'timezone' => @date_default_timezone_get(),
    'encoding' => 'UTF-8',
    'umask' => 0000,
    'mb_substitute_character' => 'none',
    'helpers' => [
        __DIR__ . '/../helpers/wrappers.php',
        __DIR__ . '/../helpers/errors.php',
        __DIR__ . '/../helpers/helpers.php',
    ],
    // the defaults
    'config directories' => [
        __ROOT__ . '/config',
        __ROOT__ . '/config/' . Application::env('ENVIRONMENT'),
    ]
];
