<?php

return [
    'directory' => __ROOT__ . '/var/cache',
    'files_permission' => 0644,
    'gc' => 1,
    'servers' => [
        [
            'host' => '127.0.0.1',
            'port' => 11211,
            'weight' => 0,
        ],
    ]
];
