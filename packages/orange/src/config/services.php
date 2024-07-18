<?php

declare(strict_types=1);

use orange\framework\Log;
use orange\framework\Data;
use orange\framework\View;
use orange\framework\Event;
use orange\framework\Input;
use orange\framework\Config;
use orange\framework\Output;
use orange\framework\Router;
use orange\framework\Dispatcher;
use orange\framework\interfaces\LogInterface;
use orange\framework\interfaces\DataInterface;
use orange\framework\interfaces\ViewInterface;
use orange\framework\interfaces\EventInterface;
use orange\framework\interfaces\InputInterface;
use orange\framework\interfaces\ConfigInterface;
use orange\framework\interfaces\OutputInterface;
use orange\framework\interfaces\RouterInterface;
use orange\framework\interfaces\ContainerInterface;
use orange\framework\interfaces\DispatcherInterface;

return [
    'log' => function (ContainerInterface $container): LogInterface {
        return Log::getInstance($container->config->log);
    },
    'events' => function (ContainerInterface $container): EventInterface {
        return Event::getInstance($container->config->events);
    },
    '@event' => 'events',
    '@request' => 'input', // alias of input
    'input' => function (ContainerInterface $container): InputInterface {
        return Input::getInstance($container->config->input);
    },
    'config' => function (ContainerInterface $container): ConfigInterface {
        return Config::getInstance($container->{'$config'});
    },
    '@databaseConfigPDO' => 'pdo',
    '@response' => 'output', // alias of output
    'output' => function (ContainerInterface $container): OutputInterface {
        return Output::getInstance($container->config->output);
    },
    'router' => function (ContainerInterface $container): RouterInterface {
        return Router::getInstance($container->config->routes, $container->input);
    },
    'dispatcher' => function (ContainerInterface $container): DispatcherInterface {
        return Dispatcher::getInstance($container);
    },
    '@phpview' => 'view', // alias of view
    'view' => function (ContainerInterface $container): ViewInterface {
        return View::getInstance($container->config->view, $container->data, $container->router);
    },
    'data' => function (ContainerInterface $container): DataInterface {
        return Data::getInstance();
    },
];
