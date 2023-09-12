<?php

declare(strict_types=1);

use app\models\personModel;
use dmyers\orange\Log;
use dmyers\orange\Data;
use dmyers\orange\View;
use dmyers\orange\Error;
use dmyers\orange\Event;
use dmyers\orange\Input;
use dmyers\orange\Config;
use dmyers\orange\Output;
use dmyers\orange\Router;
use dmyers\orange\Console;
use dmyers\orange\Dispatcher;
use dmyers\orange\interfaces\LogInterface;
use dmyers\orange\interfaces\ErrorInterface;
use dmyers\orange\interfaces\EventInterface;
use dmyers\orange\interfaces\InputInterface;
use dmyers\orange\interfaces\ConfigInterface;
use dmyers\orange\interfaces\OutputInterface;
use dmyers\orange\interfaces\RouterInterface;
use dmyers\orange\interfaces\ViewerInterface;
use dmyers\orange\interfaces\ConsoleInterface;
use dmyers\orange\interfaces\ContainerInterface;
use dmyers\orange\interfaces\DispatcherInterface;

return [
    'error' => function (ContainerInterface $container): ErrorInterface {
        // get out our config so we can append something onto it
        $config = $container->config->error;

        // get from input the request type
        $config['request type'] = $container->input->requestType();

        return Error::getInstance($config, $container->phpview, $container->output, $container->log);
    },
    'log' => function (ContainerInterface $container): LogInterface {
        return Log::getInstance($container->config->log);
    },
    'events' => function (ContainerInterface $container): EventInterface {
        return Event::getInstance($container->config->events);
    },
    'input' => function (ContainerInterface $container): InputInterface {
        return Input::getInstance($container->config->input);
    },
    'config' => function (ContainerInterface $container): ConfigInterface {
        return Config::getInstance($container->{'$config'});
    },
    'output' => function (ContainerInterface $container): OutputInterface {
        return Output::getInstance($container->config->output);
    },
    'console' => function (ContainerInterface $container): ConsoleInterface {
        return Console::getInstance($container->config->console, $container->input);
    },
    'router' => function (ContainerInterface $container): RouterInterface {
        // get out our config so we can append something onto it
        $config = $container->config->routes;

        // get from input http or https -used when making urls
        $config['isHttps']  = $container->input->isHttpsRequest();

        return Router::getInstance($config);
    },
    'dispatcher' => function (ContainerInterface $container): DispatcherInterface {
        return Dispatcher::getInstance($container);
    },
    '@phpview' => 'view', // alias of view
    'view' => function (ContainerInterface $container): ViewerInterface {
        return View::getInstance($container->config->view, $container->data);
    },
    'data' => function (ContainerInterface $container) {
        return Data::getInstance();
    },

    'pdo' => function (ContainerInterface $container) {
        // stored in the .env file specific to each server (not commited to GIT)
        return new PDO('mysql:host=' . fetchEnv('db.host') . ';dbname=' . fetchEnv('db.database'), fetchEnv('db.username'), fetchEnv('db.password'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    },

    // sample model

    'model.person' => function(ContainerInterface $container) {
        return personModel::getInstance([], $container->pdo);
    },


    // you can use anything for a service name
    // model.foo or $value
    '$test' => 'This is a test',
    // you can "get" those in 1 of 2 ways
    // $container->{'$test'}
    // $container->get('$test');
];
