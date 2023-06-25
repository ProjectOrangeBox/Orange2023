<?php

declare(strict_types=1);

/*
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$config['monolog'] = new Logger('default');
$config['monolog']->pushHandler(new StreamHandler(__ROOT__ . '/var/logs/' . date('Y-m-d') . '-log.txt', Level::Error));

return $config;

*/

use dmyers\orange\Log;

return [
    'filepath' => __ROOT__ . '/var/logs/' . date('Y-m-d') . '-log.txt',
    'threshold' => LOG::ALL,
];
