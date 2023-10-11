<?php

declare(strict_types=1);

use dmyers\orange\Log;
use dmyers\orange\Data;
use dmyers\orange\View;
use dmyers\orange\Error;
use dmyers\orange\Event;
use dmyers\orange\Input;
use dmyers\orange\Config;
use dmyers\orange\Output;
use dmyers\orange\Router;
use peel\session\Session;
use dmyers\orange\Console;
use dmyers\orange\Dispatcher;
use people\models\parentModel;
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
use peel\validate\interfaces\ValidateInterface;
use peel\validate\Validate;

/**
 * By placing the services inside a closure they are not created UNTIL they are called
 * This way you don't need to:
 * 1. connect to 1 or more databases if you don't need a database connection
 * 2. Setup a session if you don't need a session
 * 3. instantiate any class until it's needed
 * 4. allow easier mocking for testing
 * 5. allow easier overriding of any class as long as it follows the same interface
 * 6. create service alias if for example you use the same database connection on development but different ones on production
 * 
 * This saves resources and make faster applications
 */
return [
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

    'error' => function (ContainerInterface $container): ErrorInterface {
        // get out our config so we can append something onto it
        $config = $container->config->error;

        // get from input the request type
        $config['request type'] = $container->input->requestType();

        return Error::getInstance($config, $container->phpview, $container->output, $container->log);
    },

    'pdo' => function (ContainerInterface $container) {
        // stored in the .env file specific to each server (not commited to GIT)
        return new PDO('mysql:host=' . fetchEnv('db.host') . ';dbname=' . fetchEnv('db.database'), fetchEnv('db.username'), fetchEnv('db.password'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    },

    // sample model

    'model.parent' => function (ContainerInterface $container) {
        return parentModel::getInstance([], $container->pdo);
    },
    'model.childern' => function (ContainerInterface $container) {
        return parentModel::getInstance([], $container->pdo);
    },

    /* orange "peels" from the peel repro */

    'session' => function (ContainerInterface $container) {
        $config = $container->config->session;

        return Session::getInstance($config['options'], $config['saveHandler']);
    },

    'validate'=> function(ContainerInterface $container) {
        return Validate::getInstance([]);
    },

    // you can use anything for a service name
    // model.foo or $value
    '$test' => 'This is a test',
    // you can "get" those in 1 of 2 ways
    // $container->{'$test'}
    // $container->get('$test');
];
