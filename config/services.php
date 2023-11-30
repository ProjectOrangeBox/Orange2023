<?php

declare(strict_types=1);

use dmyers\orange\Log;
use dmyers\orange\Data;
use dmyers\orange\View;
use dmyers\orange\Event;
use dmyers\orange\Input;
use dmyers\orange\Config;
use dmyers\orange\Output;
use dmyers\orange\Router;
use peels\console\Console;
use peels\session\Session;
use peels\cache\FilesCache;
use peels\validate\Validate;
use dmyers\orange\Dispatcher;
use peels\quickview\QuickView;
use peels\cache\CacheInterface;
use peels\console\ConsoleInterface;
use peels\session\SessionInterface;
use dmyers\orange\interfaces\LogInterface;
use dmyers\orange\interfaces\EventInterface;
use dmyers\orange\interfaces\InputInterface;
use dmyers\orange\interfaces\ConfigInterface;
use dmyers\orange\interfaces\OutputInterface;
use dmyers\orange\interfaces\RouterInterface;
use dmyers\orange\interfaces\ViewerInterface;
use dmyers\orange\interfaces\ContainerInterface;
use peels\validate\interfaces\ValidateInterface;
use dmyers\orange\interfaces\DispatcherInterface;

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
    '@request' => 'input', // alias of input
    'input' => function (ContainerInterface $container): InputInterface {
        return Input::getInstance($container->config->input);
    },
    'config' => function (ContainerInterface $container): ConfigInterface {
        return Config::getInstance($container->{'$config'});
    },
    '@response' => 'output', // alias of output
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
    'collection' => function (ContainerInterface $container) {
        return Data::getInstance();
    },
    'pdo' => function (ContainerInterface $container) {
        // stored in the .env file specific to each server (not commited to GIT)
        return new PDO('mysql:host=' . fetchAppEnv('db.host') . ';dbname=' . fetchAppEnv('db.database'), fetchAppEnv('db.username'), fetchAppEnv('db.password'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    },

    'session' => function (ContainerInterface $container): SessionInterface {
        $config = $container->config->session;

        return Session::getInstance($config['options'], $config['saveHandler']);
    },

    'validate' => function (ContainerInterface $container): ValidateInterface {
        return Validate::getInstance($container->config->validate);
    },

    'quickView' => function (ContainerInterface $container): QuickView {
        return QuickView::getInstance($container->config->quickview, $container->output);
    },

    'cache' => function (ContainerInterface $container): CacheInterface {
        return FilesCache::getInstance($container->config->cache);
    },

    /* merged content below */
    'model.food' => function (ContainerInterface $container) {
        return \application\food\models\foodModel::getInstance([], $container->pdo);
    },
    'model.people' => function (ContainerInterface $container) {
        return \application\people\models\peopleModel::getInstance([], $container->pdo);
    },

    /* end merged contents */

    // you can use anything for a service name
    // model.foo or $value
    '$test' => 'This is a test',
    // you can "get" those in 1 of 2 ways
    // $container->{'$test'}
    // $container->get('$test');
];
