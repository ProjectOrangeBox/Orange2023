<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\exceptions\InvalidValue;
use orange\framework\interfaces\ContainerInterface;
use orange\framework\exceptions\filesystem\FileNotFound;
use orange\framework\exceptions\config\ConfigFileNotFound;
use orange\framework\exceptions\filesystem\DirectoryNotFound;

/**
 * This is just a wrapper for static "standalone" functions
 *
 * You can override these by simply calling another class when setting up your application
 */

class Application
{
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
    public static function http(array $config): ContainerInterface
    {
        // call bootstrap function which returns a container
        $container = self::bootstrap($config);

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


    /**
     * bootstrap CLI application
     */
    public static function cli(array $config): ContainerInterface
    {
        // no events, routes, "default" output
        return self::bootstrap($config);
    }

    /**
     * shared bootstrap function
     */
    public static function bootstrap(array $config): ContainerInterface
    {
        // set a undefined value which is not NULL
        define('UNDEFINED', chr(0));

        // let's make sure they setup __ROOT__
        if (!defined('__ROOT__')) {
            throw new InvalidValue('__ROOT__ not defined.');
        }

        // is root a real folder?
        if (!is_dir(__ROOT__)) {
            throw new DirectoryNotFound($config['__ROOT__']);
        }

        // switch to root
        chdir(__ROOT__);

        // set DEBUG default to false (production)
        define('DEBUG', $_ENV['DEBUG'] ?? false);

        // set ENVIRONMENT default to production
        define('ENVIRONMENT', $_ENV['ENVIRONMENT'] ?? 'production');

        switch (ENVIRONMENT) {
            case 'phpunit':
                $display_errors = 1;
                $display_startup_errors = 1;
                $error_reporting = E_ALL ^ E_NOTICE;
                break;
            case 'development':
                $display_errors = 1;
                $display_startup_errors = 1;
                $error_reporting = E_ALL ^ E_NOTICE;
                break;
            case 'testing':
                $display_errors = 1;
                $display_startup_errors = 1;
                $error_reporting = E_ALL ^ E_NOTICE;
                break;
            default:
                //fail back to production
                $display_errors = 0;
                $display_startup_errors = 0;
                $error_reporting = 0;
        }

        // ok now set those values
        ini_set('display_errors', $display_errors);
        ini_set('display_startup_errors', $display_startup_errors);
        error_reporting($error_reporting);

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

        require_once __ROOT__ . '/packages/orange/src/helpers/errors.php';

        // now try to attach the exception and error handler
        if (function_exists('exceptionHandler')) {
            set_exception_handler('exceptionHandler');
        }

        if (function_exists('errorHandler')) {
            set_error_handler('errorHandler');
        }

        // bring in our helpers
        require_once __ROOT__ . '/packages/orange/src/helpers/helpers.php';
        require_once __ROOT__ . '/packages/orange/src/helpers/wrappers.php';

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

        // we use the function in the wrappers.php files
        // because this way others can override the function and therefore
        // the creation of the container
        $container = container();

        if (!$container instanceof ContainerInterface) {
            throw new InvalidValue('container() did not return a object using the container interface.');
        }

        // save bootstrapping config for the config service
        $container->set('$config', $config);

        // load services from config
        $services = require_once $config['services'];

        // make sure they are a array
        if (!is_array($services)) {
            throw new InvalidValue('Services config file "' . $config['services'] . '" did not return an array.');
        }

        // send in our services
        $container->set($services);

        // return the container we just made
        return $container;
    }

    public static function mergeDefaultConfig(array $current, string $absFilePath, bool $recursive = true): array
    {
        if (!file_exists($absFilePath)) {
            throw new ConfigFileNotFound($absFilePath);
        }

        $defaultConfig = include $absFilePath;

        if (!is_array($defaultConfig)) {
            throw new InvalidValue('"' . $absFilePath . '" did not return an array.');
        }

        return ($recursive) ? array_replace_recursive($defaultConfig, $current) : array_replace($defaultConfig, $current);
    }
}
