<?php

declare(strict_types=1);

use orange\framework\Log;

/*
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$config['monolog'] = new Logger('default');
$config['monolog']->pushHandler(new StreamHandler(__ROOT__ . '/var/logs/' . date('Y-m-d') . '-log.txt', Level::Error));

return $config;
*/

return [
    // set log file permissions to
    'permissions' => 0644,
    // where to store the log file
    'filepath' => __ROOT__ . '/var/logs/' . date('Y-m-d') . '.log',
    // what level are we logging (this could be loaded using $_ENV[] or by testing ENVIRONMENT for example
    'threshold' => LOG::NONE,
    // what format should the log line be in
    'line format' => '%timestamp %level %message %context' . PHP_EOL,
    // what format should the %timestamp be in
    'timestamp format' => 'Y-m-d H:i:s',
];
