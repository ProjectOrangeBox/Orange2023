<?php

declare(strict_types=1);

return [
    'permissions' => 0644,
    'filepath' => __ROOT__ . '/var/logs/' . date('Y-m-d') . '-log.txt',
    'threshold' => 0,
    'line format' => '%timestamp %level %message' . PHP_EOL,
    'timestamp format' => 'Y-m-d H:i:s',
];
