<?php

declare(strict_types=1);

use Framework\Session\SaveHandlers\FilesHandler;

return [
    'options' => [
        'name' => 'session_id',
        'auto_regenerate_maxlifetime' => 7200,
        'auto_regenerate_destroy' => true,
    ],

    'saveHandler' => new FilesHandler([
        // The directory path where the session files will be saved
        'directory' => __ROOT__ . '/var/sessions',
        // A custom directory name inside the `directory` path
        'prefix' => '',
        // Match IP?
        'match_ip' => false,
        // Match User-Agent?
        'match_ua' => false,
    ]),
];
