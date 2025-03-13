<?php

declare(strict_types=1);

use orange\framework\Log;

return [
    'permissions' => 0644,
    'filepath' => __ROOT__ . '/var/logs/' . date('Y-m-d') . '.log',
    'threshold' => LOG::NONE,
    'line format' => '%timestamp %level %message %context' . PHP_EOL,
    'timestamp format' => 'Y-m-d H:i:s',
];
