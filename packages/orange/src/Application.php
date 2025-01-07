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
 * Application class responsible for bootstrapping the application in either HTTP or CLI mode.
 *
 * This class is a wrapper for the static "standalone" functions that help configure and initialize the application,
 * load required services, handle environment configurations, set up error handling, and set up the container.
 *
 * The framework allows for overrides of certain methods if extended, while still maintaining the default functionality.
 */
class Application
{
    /**
     * @var array $config Stores the application's configuration settings.
     */
    protected static array $config;

    /**
     * Bootstraps the application for HTTP requests.
     *
     * This method performs the entire HTTP request lifecycle, from routing the request to dispatching the controller
     * and sending the output. It triggers various events during the process to allow for hooks and further customization.
     *
     * @param array $config The configuration array, including required and optional settings for the application.
     *                       - 'services': Path to the services configuration file (required).
     *                       - 'config directory': Path to the directory where the configuration files are stored (required).
     *                       - 'environment': Current environment (optional).
     *                       - 'debug': Debug mode (optional).
     *                       - 'timezone': Timezone identifier (optional).
     * @return ContainerInterface Returns the container instance after bootstrapping.
     * @throws ConfigFileNotFound If the services configuration file is not found.
     * @throws DirectoryNotFound If the root directory (__ROOT__) is not valid.
     * @throws InvalidValue If the configuration or services file is invalid.
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
     * Bootstraps the application for CLI execution.
     *
     * This method performs the initial setup required for running the application in CLI mode, without routing or outputting.
     *
     * @param array $config The configuration array, including required and optional settings for the application.
     *                       - 'services': Path to the services configuration file (required).
     *                       - 'config directory': Path to the directory where the configuration files are stored (required).
     *                       - 'environment': Current environment (optional).
     *                       - 'debug': Debug mode (optional).
     *                       - 'timezone': Timezone identifier (optional).
     * @return ContainerInterface Returns the container instance after bootstrapping.
     * @throws ConfigFileNotFound If the services configuration file is not found.
     * @throws InvalidValue If the configuration or services file is invalid.
     */
    public static function cli(array $config): ContainerInterface
    {
        self::$config = $config;

        // no events, routes, "default" output
        return self::bootstrap('cli');
    }

    /**
     * Protected method for shared bootstrapping functionality.
     *
     * This method is responsible for setting up the environment, loading configuration files,
     * defining constants, checking extensions, and initializing the container.
     *
     * @param string $mode The mode in which the application is running: either 'http' or 'cli'.
     * @return ContainerInterface Returns the container instance after bootstrapping.
     * @throws InvalidValue If the __ROOT__ constant is not defined.
     * @throws DirectoryNotFound If the root directory (__ROOT__) is not valid.
     * @throws MissingRequired If the required 'mbstring' extension is not loaded.
     */
    protected static function bootstrap(string $mode): ContainerInterface
    {
        // setup a constant to indicate how this application was started
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

    /**
     * Pre-container setup. This is called before the container is set up.
     *
     * This method is responsible for loading any helper files specified in the configuration.
     *
     * @throws FileNotFound If any of the helper files do not exist.
     */
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
        require __ROOT__ . '/packages/orange/src/helpers/helpers.php';
        require __ROOT__ . '/packages/orange/src/helpers/wrappers.php';
    }

    /**
     * Sets up error handling for the application.
     *
     * This method configures the exception and error handlers for the application, based on
     * the environment and user-provided error handling functions.
     */
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

    /**
     * Bootstraps the container with services and configuration.
     *
     * This method loads the services configuration file, sets up the container, and ensures
     * the correct configuration and services are available in the container.
     *
     * @return ContainerInterface Returns the container instance.
     * @throws ConfigFileNotFound If the services configuration file is not found.
     * @throws InvalidValue If the services configuration file does not return an array.
     * @throws IncorrectInterface If the container service is not a valid closure or container instance.


     */
    protected static function bootstrapContainer(): ContainerInterface
    {
        // make sure we have services
        if (!file_exists(self::$config['services'])) {
            throw new ConfigFileNotFound('Could not locate the services configuration file.');
        }

        // load services from config
        $services = require self::$config['services'];

        // make sure they are an array
        if (!is_array($services)) {
            throw new InvalidValue('Services config file "' . self::$config['services'] . '" did not return an array.');
        }

        // replace provided over the orange defaults
        $services = array_replace_recursive(include __DIR__ . '/config/services.php', $services);

        if (!isset($services['container'])) {
            throw new InvalidValue('Container services not found.');
        }

        // Make sure container is a Closure
        if (!$services['container'] instanceof \Closure) {
            throw new IncorrectInterface('Container services not a closure.');
        }

        // now get the empty container and save a copy in our object
        $container = $services['container']()::getInstance();

        if (!$container instanceof ContainerInterface) {
            throw new IncorrectInterface('The service "container" did not return an object using the container interface.');
        }

        // save bootstrapping config for the config service in the container as self::$config
        $container->set('$config', self::$config);

        // send in our services
        $container->set($services);

        return $container;
    }

    /**
     * Post-container setup. This is called after the container is set up.
     *
     * This method is responsible for setting up constants as defined in the configuration file.
     *
     * @param ContainerInterface $container The container instance after it has been set up.
     */
    protected static function postContainer(ContainerInterface $container): void
    {
        // set up constants
        // even if there are no user constants, the config service should return an empty array
        foreach (array_replace_recursive(include __DIR__ . '/config/constants.php', $container->config->constants) as $name => $value) {
            // Constants should all be uppercase - not an option!
            $name = strtoupper($name);

            if (!defined($name)) {
                define($name, $value);
            }
        }
    }
}
