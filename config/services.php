<?php

declare(strict_types=1);

use peels\acl\Acl;
use peels\acl\User;
use peels\asset\Asset;
use orange\framework\Log;
use orange\framework\Data;
use orange\framework\View;
use peels\console\Console;
use peels\session\Session;
use peels\validate\Filter;
use orange\framework\Event;
use orange\framework\Input;
use peels\cache\DummyCache;
use orange\framework\Config;
use orange\framework\Output;
use orange\framework\Router;
use peels\validate\Validate;
use peels\quickview\QuickView;
use peels\cache\CacheInterface;
use orange\framework\Dispatcher;
use peels\handlebars\Handlebars;
use peels\console\ConsoleInterface;
use peels\session\SessionInterface;
use peels\handlebars\HandlebarsView;
use peels\acl\interfaces\AclInterface;
use application\people\models\peopleModel;
use peels\asset\Interfaces\AssetInterface;
use peels\handlebars\HandlebarsPluginCacher;
use orange\framework\interfaces\LogInterface;
use peels\acl\interfaces\UserEntityInterface;
use orange\framework\interfaces\ViewInterface;
use peels\validate\interfaces\FilterInterface;
use orange\framework\interfaces\EventInterface;
use orange\framework\interfaces\InputInterface;
use orange\framework\interfaces\ConfigInterface;
use orange\framework\interfaces\OutputInterface;
use orange\framework\interfaces\RouterInterface;
use peels\validate\interfaces\ValidateInterface;
use orange\framework\interfaces\ContainerInterface;
use orange\framework\interfaces\DispatcherInterface;

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
    '@databaseConfigPDO' => 'pdo',
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

        // get from input http or https - used when making urls
        $config['isHttps']  = $container->input->isHttpsRequest();

        return Router::getInstance($config);
    },
    'dispatcher' => function (ContainerInterface $container): DispatcherInterface {
        return Dispatcher::getInstance($container);
    },
    '@phpview' => 'view', // alias of view
    'view' => function (ContainerInterface $container): ViewInterface {
        $config = $container->config->view;

        list($config['controller'], $config['method']) = $container->router->getMatched('callback');

        return View::getInstance($config, $container->data);
    },
    'data' => function (ContainerInterface $container) {
        return Data::getInstance();
    },
    'collection' => function (ContainerInterface $container) {
        return Data::getInstance();
    },
    'pdo' => function (ContainerInterface $container) {
        $db = $_ENV['db'];

        // stored in the .env file specific to each server (not committed to GIT)
        return new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['database'], $db['username'], $db['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    },
    'session' => function (ContainerInterface $container): SessionInterface {
        $config = $container->config->session;

        return Session::getInstance($config['options'], $config['saveHandler']);
    },

    'validate' => function (ContainerInterface $container): ValidateInterface {
        return Validate::getInstance($container->config->validate);
    },

    'filter' => function (ContainerInterface $container): FilterInterface {
        return Filter::getInstance($container->validate, $container->input);
    },

    'quickView' => function (ContainerInterface $container): QuickView {
        return QuickView::getInstance($container->config->quickview, $container->output);
    },

    'cache' => function (ContainerInterface $container): CacheInterface {
        return DummyCache::getInstance($container->config->cache);
    },

    'assets' => function (ContainerInterface $container): AssetInterface {
        return Asset::getInstance($container->config->assets, $container->data);
    },

    /* merged content below */
    'model.people' => function (ContainerInterface $container) {
        return peopleModel::getInstance([], $container->pdo, $container->validate);
    },

    /* end merged contents */

    'acl' => function (ContainerInterface $container): AclInterface {
        return Acl::getInstance($container->config->acl, $container->pdo, $container->validate);
    },

    'user' => function (ContainerInterface $container): UserEntityInterface {
        return User::getInstance($container->config->acl, $container->acl, $container->session)->load();
    },

    'handlebars' => function (ContainerInterface $container) {
        return HandlebarsView::getInstance($container->config->handlebars, $container->data);
    },

    // you can use anything for a service name
    // model.foo or $value
    '$test' => 'This is a test',
    // you can "get" those in 1 of 2 ways
    // $container->{'$test'}
    // $container->get('$test');
];
