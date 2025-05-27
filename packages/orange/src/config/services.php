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
use orange\framework\Container;
use orange\framework\Dispatcher;
use orange\framework\Application;
use orange\framework\interfaces\LogInterface;
use orange\framework\interfaces\ViewInterface;
use orange\framework\interfaces\EventInterface;
use orange\framework\interfaces\InputInterface;
use orange\framework\interfaces\ConfigInterface;
use orange\framework\interfaces\OutputInterface;
use orange\framework\interfaces\RouterInterface;
use orange\framework\interfaces\ContainerInterface;
use orange\framework\interfaces\DispatcherInterface;

/*
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
 *
 * you can use anything for a service name
 * model.foo or $value
 * you can "get" those in 1 of 2 ways
 * $container->{'$test'}
 * $container->get('$test');
 */

return [
    'container' => function (array $services): ContainerInterface {
        return Container::getInstance($services);
    },
    'config' => function (ContainerInterface $container): ConfigInterface {
        return Config::getInstance(['search directories' => [
            $container->get('$configDirectory'),
            $container->get('$configDirectory') . DIRECTORY_SEPARATOR . ENVIRONMENT,
        ]]);
    },
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
    '@response' => 'output', // alias of output
    'output' => function (ContainerInterface $container): OutputInterface {
        return Output::getInstance($container->config->output, $container->input);
    },
    'router' => function (ContainerInterface $container): RouterInterface {
        return Router::getInstance($container->config->routes, $container->input);
    },
    'dispatcher' => function (ContainerInterface $container): DispatcherInterface {
        return Dispatcher::getInstance($container);
    },
    'view' => function (ContainerInterface $container): ViewInterface {
        $config = $container->config->view;

        // add the router if you want to use auto view detection
        $config['router'] = $container->router;

        return View::getInstance($config, $container->data);
    },
    'data' => function (ContainerInterface $container) {
        // main array (object) which contains data for the array
        return Data::getInstance($container->config->data);
    },
];
