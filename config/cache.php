<?php

return [
    'directory' => __ROOT__ . '/var/cache',
    'ttl' => 600,
    'ttl window' => 30,
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
