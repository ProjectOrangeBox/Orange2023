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
    const SERVICESFILENAME = 'services.php';
    const CONFIGFILENAME = 'config.php';
    const HELPERS = [
        __DIR__ . '/helpers/errors.php',
        __DIR__ . '/helpers/helpers.php',
        __DIR__ . '/helpers/wrappers.php',
    ];
    const CONFIGARRAYSERIVICE = '$config';
    const ORANGECONSTANTSFILE = __DIR__ . '/config/constants.php';
    const ORANGESERVICESFILE = __DIR__ . '/config/services.php';
    const ORANGECONFIGFILE = __DIR__ . '/config/config.php';

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
        // call bootstrap function which returns a container
        $container = static::bootstrap('http', $config);

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
        // no events, routes, "default" output
        return static::bootstrap('cli', $config);
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
     * @throws ConfigFileNotFound
     * @throws InvalidValue
     */
    protected static function bootstrap(string $mode, array $config): ContainerInterface
    {
        // set a undefined value which is not NULL
        define('UNDEFINED', chr(0));

        // setup a constant to indicate how this application was started
        define('RUN_MODE', strtolower($mode));

        // let's make sure they setup __ROOT__
        if (!defined('__ROOT__')) {
            throw new InvalidValue('The "__ROOT__" constant must be defined to indicate the root directory.');
        }

        // is root a real directory?
        if (!is_dir(__ROOT__)) {
            throw new DirectoryNotFound(__ROOT__);
        }

        // switch to root
        chdir(__ROOT__);

        // set DEBUG default to false (production)
        define('DEBUG', $_ENV['DEBUG'] ?? false);

        // set ENVIRONMENT default to production
        define('ENVIRONMENT', strtolower($_ENV['ENVIRONMENT']) ?? 'production');

        // this is part of the orange framework so we know it's there and an array
        // we also can't assume this was included with the config sent in
        $config = array_replace(static::include(self::ORANGECONFIGFILE, true), $config);

        // get our error handling defaults for the different environment types
        // these can be overridden in the passed $config array
        $envErrorsConfig = $config['environment errors config'][ENVIRONMENT] ?? $config['environment errors config']['default'];

        // ok now set those values
        ini_set('display_errors', $envErrorsConfig['display errors']);
        ini_set('display_startup_errors', $envErrorsConfig['display startup errors']);
        error_reporting($envErrorsConfig['error reporting']);

        // set timezone
        date_default_timezone_set($config['timezone']);

        // Set internal encoding.
        ini_set('default_charset', $config['encoding']);
        mb_internal_encoding($config['encoding']);
        define('CHARSET', $config['encoding']);

        // set umask to a known state
        umask(0000);

        // this extension is required and now part of php 8+
        if (!extension_loaded('mbstring')) {
            throw new MissingRequired('extension: mbstring');
        }

        // default to NO character on substitute
        mb_substitute_character('none');

        // the developer can extend this class and override these methods
        return static::postContainer(static::bootstrapContainer(static::preContainer($config)));
    }

    /**
     * Pre-container setup. This is called before the container is set up.
     *
     * This method is responsible for loading any helper files specified in the configuration.
     *
     * @param array $config
     * @return array
     * @throws FileNotFound If any of the helper files do not exist.
     * @throws ConfigFileNotFound
     * @throws InvalidValue
     */
    protected static function preContainer(array $config): array
    {
        // load any helpers they might have loaded
        foreach (array_replace($config['helpers'] ?? [], self::HELPERS) as $helperFile) {
            if (!file_exists($helperFile)) {
                throw new FileNotFound($helperFile);
            }

            static::include($helperFile, true, false);
        }

        // now errorHandler() & errorHandler() should be setup
        // try to attach the exception and error handler
        if (function_exists('errorHandler')) {
            set_error_handler('errorHandler');
        }

        if (function_exists('errorHandler')) {
            set_exception_handler('exceptionHandler');
        }

        return $config;
    }

    /**
     * Bootstraps the container with services and configuration.
     *
     * This method loads the services configuration file, sets up the container, and ensures
     * the correct configuration and services are available in the container.
     *
     * @param array $config
     * @return ContainerInterface Returns the container instance.
     * @throws ConfigFileNotFound If the services configuration file is not found.
     * @throws InvalidValue If the services configuration file does not return an array.
     * @throws IncorrectInterface If the container service is not a valid closure or container instance.
     */
    protected static function bootstrapContainer(array $config): ContainerInterface
    {
        // if they provide a services config file this overrides ALL others
        if (isset($config['services file'])) {
            $services = static::include($config['services file'], true);
        } else {
            // user config directory
            $configDirectory = $config['config directory'] ?? '';

            $environment = $config['environment'] ?? false;

            if ($environment !== false) {
                $environmentDirectory = ($environment === true) ? ENVIRONMENT : $environment;

                // user environment config services
                $userEnvironmentServices = static::include($configDirectory . DIRECTORY_SEPARATOR . $environmentDirectory . DIRECTORY_SEPARATOR . self::SERVICESFILENAME, false);
            } else {
                $userEnvironmentServices = [];
            }

            // final services array
            $services = array_replace(static::include(self::ORANGESERVICESFILE, true), static::include($configDirectory . DIRECTORY_SEPARATOR . self::SERVICESFILENAME, false), $userEnvironmentServices);
        }

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
        $container->set(self::CONFIGARRAYSERIVICE, $config);

        return $container;
    }

    /**
     * Post-container setup. This is called after the container is set up.
     *
     * If you extend this class this is a good place to do any post container code :P
     *
     * @param ContainerInterface $container The container instance after it has been set up.
     * @return ContainerInterface
     * @throws ConfigFileNotFound
     * @throws InvalidValue
     */
    protected static function postContainer(ContainerInterface $container): ContainerInterface
    {
        // set up constants local constants + any user supplied in the user config folder
        foreach (array_replace(static::include(self::ORANGECONSTANTSFILE, true), $container->config->constants) as $name => $value) {
            // Constants should all be uppercase - not an option!
            $name = strtoupper($name);

            if (!defined($name)) {
                define($name, $value);
            }
        }

        return $container;
    }

    /**
     * include a config file which must return an array
     * this only throws an exception if the file does not return an array
     * it will always return and array even if empty
     *
     * @param string $configFilePath
     * @return array|null
     * @throws ConfigFileNotFound
     * @throws InvalidValue
     */
    protected static function include(string $configFilePath, bool $required = false, bool $isArray = true): array|null
    {
        $config = [];

        $absoluteConfigFile = realpath($configFilePath);

        if ($absoluteConfigFile === false && $required) {
            throw new ConfigFileNotFound($configFilePath);
        }

        if (is_string($absoluteConfigFile)) {
            $config = require $absoluteConfigFile;

            if ($isArray && !is_array($config)) {
                throw new InvalidValue('File "' . $configFilePath . '" did not return an array.');
            }
        }

        return $isArray ? $config : null;
    }
}
