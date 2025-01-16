<?php

declare(strict_types=1);

use peels\acl\Acl;
use peels\acl\User;
use peels\disc\Disc;
use peels\asset\Asset;
use peels\cookie\Cookie;
use peels\console\Console;
use peels\session\Session;
use peels\validate\Filter;
use peels\cache\DummyCache;
use orange\framework\Router;
use peels\language\Language;
use peels\validate\Validate;
use peels\collector\Collector;
use peels\mergeView\MergeView;
use peels\negotiate\Negotiate;
use peels\quickview\QuickView;
use peels\cache\CacheInterface;
use peels\cache\MemcachedCache;
use peels\cookie\CookieInterface;
use peels\console\ConsoleInterface;
use peels\session\SessionInterface;
use peels\handlebars\HandlebarsView;
use peels\language\LanguageInterface;
use application\join\models\JoinModel;
use peels\acl\interfaces\AclInterface;
use peels\collector\CollectorInterface;
use peels\negotiate\NegotiateInterface;
use application\child\models\ChildModel;
use application\people\models\ColorModel;
use application\people\models\PeopleModel;
use peels\asset\Interfaces\AssetInterface;
use peels\acl\interfaces\UserEntityInterface;
use orange\framework\interfaces\ViewInterface;
use peels\validate\interfaces\FilterInterface;
use orange\framework\interfaces\RouterInterface;
use peels\validate\interfaces\ValidateInterface;
use orange\framework\interfaces\ContainerInterface;
use peels\cache\FilesCache;

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
 * This saves resources and makes faster applications
 *
 * These are merged over the default services
 * If you want to replace a default service simply redeclare it here
 * you can see the defaults in packages/orange/src/config/services.php
 */

return [
    // the default route do not use the cache but supports it so we override it in our services config
    'router' => function (ContainerInterface $container): RouterInterface {
        // if in production then we can cache the routes for faster access
        // this key would need to be flushed in order enable new routes
        if (ENVIRONMENT == 'production') {
            return Router::getInstance($container->config->routes, $container->input, $container->cache);
        } else {
            return Router::getInstance($container->config->routes, $container->input);
        }
    },
    'console' => function (ContainerInterface $container): ConsoleInterface {
        return Console::getInstance($container->config->console, $container->input);
    },
    'mergeView' => function (ContainerInterface $container): ViewInterface {
        return MergeView::getInstance($container->config->mergeview, $container->data);
    },
    'quickView' => function (ContainerInterface $container): QuickView {
        return QuickView::getInstance($container->config->quickview, $container->output);
    },
    'collection' => function (ContainerInterface $container): CollectorInterface {
        return Collector::getInstance($container->config->collection);
    },
    '@databaseConfigPDO' => 'pdo', // alias of pdo
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

    '@cache' => 'fcache',

    'fcache'=>function (ContainerInterface $container): CacheInterface {
        return FilesCache::getInstance($container->config->cache);
    },

    'mcache' => function (ContainerInterface $container): CacheInterface {
        return MemcachedCache::getInstance($container->config->cache);
    },

    'assets' => function (ContainerInterface $container): AssetInterface {
        return Asset::getInstance($container->config->assets, $container->data);
    },

    '@cookies' => 'cookie', // alias of cookie
    'cookie' => function (ContainerInterface $container): CookieInterface {
        return Cookie::getInstance($container->config->cookie, $container->input, $container->output);
    },

    /* merged content below */

    'model.people' => function (ContainerInterface $container) {
        return PeopleModel::getInstance([], $container->pdo, $container->validate);
    },

    'model.color' => function (ContainerInterface $container) {
        return ColorModel::getInstance([], $container->pdo, $container->validate);
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
    'language' =>  function (ContainerInterface $container): LanguageInterface {
        $config = $container->config->language;

        // the 2 we support it will pick the best based on the sent header
        $config['default language'] = $container->negotiate->language(['en', 'fr']);

        return Language::getInstance($config, $container->output);
    },
    'fig' => function (ContainerInterface $container) {
        // this is static so we need to load it once
        require_once __DIR__ . '/../packages/peels/fig/src/fig.php';

        // does not return anything this only sets up fig
        fig::configure($container->config->fig, $container->data);
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
