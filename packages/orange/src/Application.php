<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\exceptions\InvalidValue;
use orange\framework\exceptions\MissingRequired;
use orange\framework\exceptions\IncorrectInterface;
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
    protected static array $config;

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
        self::$config = $config;

        // call bootstrap function which returns a container
        $container = self::bootstrap('http');

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

        return $container;
    }

    /**
     * bootstrap CLI application
     */
    public static function cli(array $config): ContainerInterface
    {
        self::$config = $config;

        // no events, routes, "default" output
        return self::bootstrap('cli');
    }

    /** protected */

    /**
     * shared bootstrap function
     */
    protected static function bootstrap(string $mode): ContainerInterface
    {
        // setup a contant to indicate how this application was started
        define('RUN_MODE', strtolower($mode));

        // this is part of the orange framework so we know it's there
        self::$config = array_replace_recursive(include __DIR__ . '/config/config.php', self::$config);

        // let's make sure they setup __ROOT__
        if (!defined('__ROOT__')) {
            throw new InvalidValue('__ROOT__ not defined.');
        }

        // is root a real directory?
        if (!is_dir(__ROOT__)) {
            throw new DirectoryNotFound(self::$config['__ROOT__']);
        }

        // set a undefined value which is not NULL
        define('UNDEFINED', chr(0));

        // switch to root
        chdir(__ROOT__);

        // set DEBUG default to false (production)
        define('DEBUG', $_ENV['DEBUG'] ?? false);

        // set ENVIRONMENT default to production
        define('ENVIRONMENT', strtolower($_ENV['ENVIRONMENT']) ?? 'production');

        // get our error handling defaults for the different environment types
        // these can be overridden in the passed $config array
        $envErrorsConfig = self::$config['environment errors config'][ENVIRONMENT] ?? self::$config['environment errors config']['default'];

        // ok now set those values
        ini_set('display_errors', $envErrorsConfig['display errors']);
        ini_set('display_startup_errors', $envErrorsConfig['display startup errors']);
        error_reporting($envErrorsConfig['error reporting']);

        // set timezone
        date_default_timezone_set(self::$config['timezone']);

        // Set internal encoding.
        ini_set('default_charset', self::$config['encoding']);
        mb_internal_encoding(self::$config['encoding']);
        define('CHARSET', self::$config['encoding']);

        // set umask to a known state
        umask(0000);

        // this extension is required and now part of my 8+
        if (!extension_loaded('mbstring')) {
            throw new MissingRequired('extension: mbstring');
        }

        // default to NO character on substitute
        mb_substitute_character('none');

        // the developer can extend this class and override these methods
        // just make sure they still do the default functionality
        self::preContainer();

        // the developer can extend this class and override these methods
        // just make sure they still do the default functionality
        self::bootstrapErrorHandling();

        // ok now we can setup the container
        // the developer can extend this class and override these methods
        // just make sure they still do the default functionality
        $container = self::bootstrapContainer();

        // the developer can extend this class and override these methods
        // just make sure they still do the default functionality
        self::postContainer($container);

        return $container;
    }

    // these have been split of incase you need to override them
    protected static function preContainer(): void
    {
        // load any helpers they might have loaded
        foreach (self::$config['helpers'] as $helperFile) {
            if (!file_exists($helperFile)) {
                throw new FileNotFound($helperFile);
            }

            require $helperFile;
        }

        // bring in our required helpers
        // these are all wrapped in function_exists() so even these could be overridden if
        // another helper / bootstrap defines them
        require __ROOT__ . '/packages/orange/src/helpers/helpers.php';
        require __ROOT__ . '/packages/orange/src/helpers/wrappers.php';
    }

    protected static function bootstrapErrorHandling(): void
    {
        // this is required either default orange framework one or the end user provides
        require __ROOT__ . '/packages/orange/src/helpers/errors.php';

        // now try to attach the exception and error handler
        if (function_exists('exceptionHandler')) {
            set_exception_handler('exceptionHandler');
        }

        if (function_exists('errorHandler')) {
            set_error_handler('errorHandler');
        }
    }

    protected static function bootstrapContainer(): ContainerInterface
    {
        // make sure we have services
        if (!file_exists(self::$config['services'])) {
            throw new ConfigFileNotFound('Could not locate the services configuration file.');
        }

        // load services from config
        $services = require self::$config['services'];

        // make sure they are a array
        if (!is_array($services)) {
            throw new InvalidValue('Services config file "' . self::$config['services'] . '" did not return an array.');
        }

        // replace provided over the orange defaults
        $services = array_replace_recursive(include __DIR__ . '/config/services.php', $services);

        if (!isset($services['container'])) {
            throw new InvalidValue('Container services not found.');
        }

        // Make sure container is an Closure
        if (!$services['container'] instanceof \Closure) {
            throw new IncorrectInterface('Container services not a closure.');
        }

        // now get the empty container and save a copy in our object
        $container = $services['container']()::getInstance();

        if (!$container instanceof ContainerInterface) {
            throw new IncorrectInterface('The service "container" did not return a object using the container interface.');
        }

        // save bootstrapping config for the config service in the container as self::$config
        $container->set('$config', self::$config);

        // send in our services
        $container->set($services);

        return $container;
    }

    protected static function postContainer(ContainerInterface $container): void
    {
        // set up constants
        // even if there are no user constants the config service should return an empty array
        foreach (array_replace_recursive(include __DIR__ . '/config/constants.php', $container->config->constants) as $name => $value) {
            // Constants should all be uppercase - not an option!
            $name = strtoupper($name);

            if (!defined($name)) {
                define($name, $value);
            }
        }
    }
}
