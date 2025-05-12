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

    protected static string $servicesFileName = 'services.php';

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
        static::$config = $config;

        // call bootstrap function which returns a container
        $container = static::bootstrap('http');

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
        static::$config = $config;

        // no events, routes, "default" output
        return static::bootstrap('cli');
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
        // set a undefined value which is not NULL
        define('UNDEFINED', chr(0));

        // setup a constant to indicate how this application was started
        define('RUN_MODE', strtolower($mode));

        // this is part of the orange framework so we know it's there and an array
        // we also can't assume this was included with the config sent in
        $defaultConfig = static::include(__DIR__ . '/config/config.php');

        static::$config = array_replace($defaultConfig, static::$config);

        // let's make sure they setup __ROOT__
        if (!defined('__ROOT__')) {
            throw new InvalidValue('The "__ROOT__" constant must be defined to indicate the root directory.');
        }

        // is root a real directory?
        if (!is_dir(__ROOT__)) {
            throw new DirectoryNotFound(static::$config['__ROOT__']);
        }

        // switch to root
        chdir(__ROOT__);

        // set DEBUG default to false (production)
        define('DEBUG', $_ENV['DEBUG'] ?? false);

        // set ENVIRONMENT default to production
        define('ENVIRONMENT', strtolower($_ENV['ENVIRONMENT']) ?? 'production');

        // if this is NOT set then no environment directory will be added
        static::$config['environment'] = ENVIRONMENT;

        // get our error handling defaults for the different environment types
        // these can be overridden in the passed $config array
        $envErrorsConfig = static::$config['environment errors config'][ENVIRONMENT] ?? static::$config['environment errors config']['default'];

        // ok now set those values
        ini_set('display_errors', $envErrorsConfig['display errors']);
        ini_set('display_startup_errors', $envErrorsConfig['display startup errors']);
        error_reporting($envErrorsConfig['error reporting']);

        // set timezone
        date_default_timezone_set(static::$config['timezone']);

        // Set internal encoding.
        ini_set('default_charset', static::$config['encoding']);
        mb_internal_encoding(static::$config['encoding']);
        define('CHARSET', static::$config['encoding']);

        // set umask to a known state
        umask(0000);

        // this extension is required and now part of php 8+
        if (!extension_loaded('mbstring')) {
            throw new MissingRequired('extension: mbstring');
        }

        // default to NO character on substitute
        mb_substitute_character('none');

        // the developer can extend this class and override these methods
        // just make sure they still do the default functionality
        static::preContainer();

        // the developer can extend this class and override these methods
        // just make sure they still do the default functionality
        static::bootstrapErrorHandling();

        // ok now we can setup the container
        // the developer can extend this class and override these methods
        // just make sure they still do the default functionality
        $container = static::bootstrapContainer();

        // the developer can extend this class and override these methods
        static::postContainer($container);

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
        foreach (static::$config['helpers'] as $helperFile) {
            if (!file_exists($helperFile)) {
                throw new FileNotFound($helperFile);
            }

            require $helperFile;
        }

        // bring in our required helpers
        require __DIR__ . '/helpers/helpers.php';
        require __DIR__ . '/helpers/wrappers.php';
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
        require __DIR__ . '/helpers/errors.php';

        if (function_exists('errorHandler')) {
            set_error_handler('errorHandler');
        }

        // now try to attach the exception and error handler
        if (function_exists('exceptionHandler')) {
            set_exception_handler('exceptionHandler');
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
        // orange default services (the filename is fixed since it is a orange file)
        $defaultServices = require __DIR__ . '/config/services.php';

        // user config services
        if (isset(static::$config['services file'])) {
            if (!file_exists(static::$config['services file'])) {
                throw new FileNotFound(static::$config['services file']);
            }
            
            $userServices = require static::$config['services file'];
            
            // we only use the services they provided
            $userEnvironmentServices = [];
        } else {
            // dynamic user services
            $userServices = static::findServiceConfigFile(static::$config['config directory'] ?? '');

            // user environment config services
            $userEnvironmentServices = static::findServiceConfigFile(static::$config['config directory'] ?? '' . '/' . static::$config['environment']);
        }

        // final services array
        $services = array_replace($defaultServices, $userServices, $userEnvironmentServices);

        if (!isset($services['container'])) {
            throw new InvalidValue('Container Service not found.');
        }

        // Make sure container is a Closure
        if (!$services['container'] instanceof \Closure) {
            throw new IncorrectInterface('Container services not a closure.');
        }

        // now get the empty container and save a copy in our object
        $container = $services['container']($services);

        if (!$container instanceof ContainerInterface) {
            throw new IncorrectInterface('The service "container" did not return an object using the container interface.');
        }

        // add our configuration
        $container->set('$config', static::$config);

        return $container;
    }

    /**
     * Try and locate the services config file
     *
     * @param string $directory
     * @return array
     * @throws ConfigFileNotFound
     */
    protected static function findServiceConfigFile(string $directory): array
    {
        $return = [];

        if (file_exists($directory . '/' . static::$servicesFileName)) {
            $return = require $directory . '/' . static::$servicesFileName;
        }

        return $return;
    }

    /**
     * Post-container setup. This is called after the container is set up.
     *
     * If you extend this class this is a good place to do any post container code :P
     *
     * @param ContainerInterface $container The container instance after it has been set up.
     */
    protected static function postContainer(ContainerInterface $container): void
    {

        // set up constants local constants + any user supplied in the user config folder
        $constants = static::include(__DIR__ . '/config/constants.php') + $container->config->constants;

        foreach ($constants as $name => $value) {
            // Constants should all be uppercase - not an option!
            $name = strtoupper($name);
            if (!defined($name)) {
                define($name, $value);
            }
        }
    }

    /**
     * include a config file which must return an array
     * this only throws an exception if the file does not return an array
     * it will always return and array even if empty
     *
     * @param string $path
     * @return array
     * @throws InvalidValue
     */
    protected static function include(string $path): array
    {
        $config = [];

        if (file_exists($path)) {
            $config = require $path;

            if (!is_array($config)) {
                throw new InvalidValue('Config file "' . $path . '" did not return an array.');
            }
        }

        return $config;
    }
}
