<?php

declare(strict_types=1);

use dmyers\orange\exceptions\InvalidValue;
use dmyers\orange\exceptions\ConfigFileNotFound;
use dmyers\orange\interfaces\ContainerInterface;

define('NOVALUE', '__#NOVALUE#__');

// orange "required" helpers
require __DIR__ . '/helpers/env.php';
require __DIR__ . '/helpers/helpers.php';
require __DIR__ . '/helpers/errors.php';
require __DIR__ . '/helpers/wrappers.php';

/**
 * bootstrap http application
 */
if (!function_exists('http')) {
    /**
     * required:
     *
     * 'services' the absolute path to the services config file. usually /app/config/services.php
     * 'config folder' the absolute path to where the configuration files are stored
     *
     * Optional:
     * 'environment' the current environment (different for each system and usually set using fetchAppEnv('ENVIRONMENT') to get it from the .env
     *      default 'production'
     * 'debug' the current debug level true or false usually set using fetchAppEnv('DEBUG', false) to get it from the .env
     *      default false;
     *
     * 'timezone' PHP valid timezone identifier, like UTC, Africa/Lagos, Asia/Hong_Kong, or Europe/Lisbon.
     *      default 'UTC'
     *
     */
    function http(array $config): ContainerInterface
    {
        // call bootstrap function which returns a container
        $container = bootstrap($config);

        // call event
        $container->events->trigger('before.router', $container->input);

        // match uri & method to route
        $container->router->match($container->input->requestUri(), $container->input->requestMethod());

        // call event
        $container->events->trigger('before.controller', $container->router, $container->input);

        // dispatch route
        $container->output->write($container->dispatcher->call($container->router->getMatched()));

        // call event
        $container->events->trigger('before.output', $container->router, $container->input, $container->output);

        // send header, status code and output
        $container->output->send();

        // call event
        $container->events->trigger('before.shutdown', $container->router, $container->input, $container->output);

        // return container
        return $container;
    }
}

/**
 * bootstrap CLI application
 */
if (!function_exists('cli')) {
    function cli(array $config): ContainerInterface
    {
        // no events, routes, "default" output
        return bootstrap($config);
    }
}

/**
 * shared bootstrap function
 */
if (!function_exists('bootstrap')) {
    function bootstrap(array $config): ContainerInterface
    {
        if (isset($config['timezone'])) {
            date_default_timezone_set($config['timezone']);
        } else {
            date_default_timezone_set('UTC');
        }

        define('DEBUG', $config['debug'] ?? false);
        define('ENVIRONMENT', $config['environment'] ?? 'production');

        // load user constants if present
        if (file_exists($config['config folder'] . '/constants.php')) {
            require_once($config['config folder'] . '/constants.php');
        }

        // load the environmental constants if present
        if (file_exists($config['config folder'] . '/' . ENVIRONMENT . '/constants.php')) {
            require_once($config['config folder'] . '/' . ENVIRONMENT . '/constants.php');
        }

        // set umask to a known state
        umask(0000);

        switch (ENVIRONMENT) {
            case 'phpunit':
                ini_set('display_errors', '1');
                ini_set('display_startup_errors', '1');
                error_reporting(E_ALL ^ E_NOTICE);
                break;
            case 'development':
                ini_set('display_errors', '1');
                ini_set('display_startup_errors', '1');
                error_reporting(E_ALL ^ E_NOTICE);
                break;
            case 'testing':
                ini_set('display_errors', '1');
                ini_set('display_startup_errors', '1');
                error_reporting(E_ALL ^ E_NOTICE);
                break;
            default: //production
                ini_set('display_errors', '0');
                ini_set('display_startup_errors', '0');
        }

        if (extension_loaded('mbstring')) {
            define('MB_ENABLED', true);
            mb_substitute_character('none');
        } else {
            define('MB_ENABLED', false);
        }

        // make sure we have services
        if (!isset($config['services']) || !file_exists($config['services'])) {
            throw new ConfigFileNotFound('Could not locate the services configuration file.');
        }

        // load services from config
        $services = require $config['services'];

        // make sure they are a array
        if (!is_array($services)) {
            throw new InvalidValue('Services config file "' . $config['services'] . '" did not return an array.');
        }

        // use the container() function to instantiate the container
        // this way overriding this global function allows us to override
        // the generation of the main container with something else
        // as long as it implements the container interface
        $container = container();

        if (!$container instanceof ContainerInterface) {
            throw new InvalidValue('container() did not return a class using the container interface.');
        }

        // setup the container
        $container->setServices($services);

        // save bootstrapping config for the config service
        $container->set('$config', $config);

        // return the container we just made
        return $container;
    }
}
