<?php

declare(strict_types=1);

use orange\framework\Config;
use orange\framework\interfaces\ConfigInterface;
use orange\framework\interfaces\ContainerInterface;

return [
    'config' => function (ContainerInterface $container): ConfigInterface {
        return Config::getInstance($container->{'$config'});
    },
    'log' => \orange\framework\Log::class,
    'events' => \orange\framework\Event::class,
    'input' => \orange\framework\Input::class,
    'output' => \orange\framework\Output::class,
    'router' => \orange\framework\Router::class,
    'dispatcher' => \orange\framework\Dispatcher::class,
    'view' => \orange\framework\View::class,
    'data' => \orange\framework\Data::class,

    '@event' => 'events', // alias of events
    '@request' => 'input', // alias of input
    '@response' => 'output', // alias of output
];
