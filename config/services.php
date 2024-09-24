<?php

declare(strict_types=1);

use peels\acl\Acl;
use peels\acl\User;
use peels\disc\Disc;
use peels\asset\Asset;
use peels\cookie\Cookie;
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
use peels\language\Language;
use peels\validate\Validate;
use peels\collector\Collector;
use peels\mergeView\MergeView;
use peels\negotiate\Negotiate;
use peels\quickview\QuickView;
use peels\cache\CacheInterface;
use orange\framework\Dispatcher;
use peels\cookie\CookieInterface;
use peels\console\ConsoleInterface;
use peels\session\SessionInterface;
use peels\handlebars\HandlebarsView;
use peels\language\LanguageInterface;
use application\join\models\JoinModel;
use peels\acl\interfaces\AclInterface;
use peels\negotiate\NegotiateInterface;
use application\child\models\ChildModel;
use application\people\models\PeopleModel;
use peels\asset\Interfaces\AssetInterface;
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
use peels\collector\CollectorInterface;

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
    'config' => function (ContainerInterface $container): ConfigInterface {
        return Config::getInstance($container->{'$config'});
    },
    'log' => function (ContainerInterface $container): LogInterface {
        return Log::getInstance($container->config->log);
    },
    'events' => function (ContainerInterface $container): EventInterface {
        return Event::getInstance($container->config->events);
    },
    '@event'=>'events',
    '@request' => 'input', // alias of input
    'input' => function (ContainerInterface $container): InputInterface {
        return Input::getInstance($container->config->input);
    },
    '@databaseConfigPDO' => 'pdo', // alias of pdo
    '@response' => 'output', // alias of output
    'output' => function (ContainerInterface $container): OutputInterface {
        return Output::getInstance($container->config->output);
    },
    'console' => function (ContainerInterface $container): ConsoleInterface {
        return Console::getInstance($container->config->console, $container->input);
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
    'mergeView' => function (ContainerInterface $container): ViewInterface {
        return MergeView::getInstance($container->config->mergeview, $container->data);
    },
    'quickView' => function (ContainerInterface $container): QuickView {
        return QuickView::getInstance($container->config->quickview, $container->output);
    },

    'data' => function (ContainerInterface $container) {
        return Data::getInstance($container->config->data);
    },
    'collection' => function (ContainerInterface $container): CollectorInterface {
        return Collector::getInstance($container->config->collection);
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

    'cache' => function (ContainerInterface $container): CacheInterface {
        return DummyCache::getInstance($container->config->cache);
    },

    'assets' => function (ContainerInterface $container): AssetInterface {
        return Asset::getInstance($container->config->assets, $container->data);
    },

    'cookie' => function (ContainerInterface $container): CookieInterface {
        return Cookie::getInstance($container->input, $container->ouput);
    },

    /* merged content below */

    'model.people' => function (ContainerInterface $container) {
        return PeopleModel::getInstance([], $container->pdo, $container->validate);
    },

    'model.child' => function (ContainerInterface $container) {
        return ChildModel::getInstance([], $container->pdo, $container->validate);
    },

    'model.join' => function (ContainerInterface $container) {
        return JoinModel::getInstance([], $container->pdo, $container->validate);
    },

    /* end merged contents */

    'acl' => function (ContainerInterface $container): AclInterface {
        return Acl::getInstance($container->config->acl, $container->pdo, $container->validate);
    },

    // returns actual user object
    'user' => function (ContainerInterface $container): UserEntityInterface {
        return User::getInstance($container->config->acl, $container->acl, $container->session)->load();
    },

    'handlebars' => function (ContainerInterface $container) {
        return HandlebarsView::getInstance($container->config->handlebars, $container->data);
    },
    'negotiate' => function (ContainerInterface $container): NegotiateInterface {
        return Negotiate::getInstance($container->input);
    },
    'language'=>  function (ContainerInterface $container): LanguageInterface {
        $config = $container->config->language;

        // the 2 we support it will pick the best based on the sent header
        $config['default language'] = $container->negotiate->language(['en','fr']);
        
        return Language::getInstance($config);
    },
    'fig' => function (ContainerInterface $container) {
        require_once __DIR__.'/../packages/peels/fig/src/fig.php';

        // does not return anything this only sets up fig
        fig::configure($container->config->fig);
    },
    'disc' => function (ContainerInterface $container) {
        // does not return anything this only sets up disc
        Disc::configure($container->config->disc);
    },
    // you can use anything for a service name
    // model.foo or $value
    '$test' => 'This is a test',
    // you can "get" those in 1 of 2 ways
    // $container->{'$test'}
    // $container->get('$test');
];
