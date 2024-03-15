<?php

declare(strict_types=1);

use orange\framework\exceptions\InvalidValue;
use orange\framework\exceptions\ConfigFileNotFound;
use orange\framework\exceptions\FileNotFound;
use orange\framework\exceptions\FolderNotFound;
use orange\framework\interfaces\ContainerInterface;

// set a undefined value which is not NULL
define('UNDEFINED', chr(0));

/**
 * bootstrap http application
 */
if (!function_exists('http')) {
    /**
     * required:
     *
     * 'services' the absolute path to the services config file. usually /app/config/services.php
     *
     * Optional:
     * 'environment' the current environment (different for each system and usually set using $_ENV['ENVIRONMENT'] ?? 'production' to get it from the .env
     *      default 'production'
     * 'debug' the current debug level true or false usually set using $_ENV['DEBUG'] ?? false to get it from the .env
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
        // let's make sure they setup __ROOT__
        if (!defined('__ROOT__')) {
            throw new InvalidValue('__ROOT__ not defined.');
        }

        // is root a real folder?
        if (!is_dir(__ROOT__)) {
            throw new FolderNotFound($config['__ROOT__']);
        }

        // switch to root
        chdir(__ROOT__);

        define('DEBUG', $_ENV['DEBUG'] ?? false);
        define('ENVIRONMENT', $_ENV['ENVIRONMENT'] ?? 'production');

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
            default: //fail back to production
                ini_set('display_errors', '0');
                ini_set('display_startup_errors', '0');
        }

        // set timezone
        $timezone = $config['timezone'] ?? @date_default_timezone_get();

        date_default_timezone_set($timezone);

        // Set internal encoding.
        $encoding = $config['encoding'] ?? 'UTF-8';

        @ini_set('default_charset', $encoding);
        mb_internal_encoding($encoding);
        define('CHARSET', $encoding);

        // set umask to a known state
        umask(0000);

        if (extension_loaded('mbstring')) {
            define('MB_ENABLED', true);
            mb_substitute_character('none');
        } else {
            define('MB_ENABLED', false);
        }

        // load any helpers they might have loaded
        if (isset($config['helpers']) && is_array($config['helpers'])) {
            foreach ($config['helpers'] as $helperFile) {
                if (!file_exists($helperFile)) {
                    throw new FileNotFound($helperFile);
                }

                require_once $helperFile;
            }
        }

        require __ROOT__ . '/packages/orange/src/helpers/errors.php';

        // now try to attach the exception and error handler
        if (function_exists('exceptionHandler')) {
            set_exception_handler('exceptionHandler');
        }

        if (function_exists('errorHandler')) {
            set_error_handler('errorHandler');
        }

        require __ROOT__ . '/packages/orange/src/helpers/helpers.php';
        require __ROOT__ . '/packages/orange/src/helpers/wrappers.php';

        $configFolder = realpath(rtrim($config['config folder'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);

        // load user constants if present
        if (file_exists($configFolder . 'constants.php')) {
            require_once $configFolder . 'constants.php';
        }

        // load the environmental constants if present
        if (file_exists($configFolder . ENVIRONMENT . DIRECTORY_SEPARATOR . 'constants.php')) {
            require_once $configFolder . ENVIRONMENT . DIRECTORY_SEPARATOR . 'constants.php';
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
        // the generation of the main container
        // with something else as long as it implements the container interface
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
