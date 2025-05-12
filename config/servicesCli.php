<?php

declare(strict_types=1);

use peels\disc\Disc;
use peels\console\Console;
use peels\mergeView\MergeView;
use peels\console\ConsoleInterface;
use orange\framework\interfaces\ViewInterface;
use orange\framework\interfaces\ContainerInterface;

return [
    'console' => function (ContainerInterface $container): ConsoleInterface {
        return Console::getInstance($container->config->console, $container->input);
    },
    'mergeView' => function (ContainerInterface $container): ViewInterface {
        return MergeView::getInstance($container->config->mergeview, $container->data);
    },
    '@databaseConfigPDO' => 'pdo', // alias of pdo
    'pdo' => function (ContainerInterface $container) {
        $db = $_ENV['db'];

        // stored in the .env file specific to each server (not committed to GIT)
        return new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['database'], $db['username'], $db['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    },
    'disc' => function (ContainerInterface $container) {
        // does not return anything this only sets up disc
        Disc::configure($container->config->disc);
    },
];
