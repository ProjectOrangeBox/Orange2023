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
use orange\framework\exceptions\config\InvalidConfigurationValue;
use orange\framework\exceptions\config\ConfigFileDidNotReturnAnArray;

/**
 * Overview of Application.php
 *
 * This file defines the Application class in the orange\framework namespace.
 * It acts as the entry point and bootstrapper for Orange applications, providing both HTTP and CLI modes.
 * Its main responsibility is to initialize the environment, load configuration, prepare services, and then run the application lifecycle.
 *
 * ⸻
 *
 * 1. Core Responsibilities
 *   1. Bootstrap the application
 *    •  Defines constants like UNDEFINED, RUN_MODE, DEBUG, CHARSET.
 *    •  Verifies the root directory (__ROOT__).
 *    •  Loads environment variables (from system and .env files).
 *    •  Loads configuration files and merges them.
 *    •  Sets PHP runtime settings (errors, encoding, timezone, umask).
 *   2. Start different modes
 *    •  http() – runs the full HTTP lifecycle (routing, controller dispatching, output, shutdown).
 *    •  cli() – sets up the environment and returns the container for CLI usage.
 *   3. Dependency Injection (DI) Container setup
 *    •  Bootstraps services (from config files).
 *    •  Ensures a valid container is created via a closure.
 *    •  Makes the container globally available through Application::$container.
 *    •  Exposes configuration values as $application.KEY inside the container.
 *
 * ⸻
 *
 * 2. Lifecycle for HTTP Application
 *   1. Call http() → calls bootstrap('http', $config).
 *   2.  Triggers events in sequence:
 *     •  before.router → before routing.
 *     •  before.controller → before dispatching the matched route.
 *     •  before.output → before sending response.
 *     •  before.shutdown → before shutdown.
 *   3.  Handles routing, dispatching controllers, writing and sending output.
 *
 * This structure makes the application event-driven, letting developers hook into different stages.
 *
 * ⸻
 *
 * 3. Configuration Handling
 *     •  Environment (loadEnvironment)
 * Loads environment variables into a static $env array. Parses .ini files if provided. Defines the ENVIRONMENT constant (default: production).
 *     •  Config Files (loadConfig)
 * Loads application configs and merges them with defaults. Supports cascading configs from multiple directories and environment-specific files.
 *     •  Constants
 * Loads config-defined constants, enforcing uppercase.
 *
 * ⸻
 *
 * 4. Globals Handling
 *
 * Provides an abstraction over PHP superglobals ($_POST, $_SERVER, etc.) through:
 *     •  fromGlobals($key) – fetches a specific global or all.
 *     •  setGlobals($globals) – allows overriding or adding globals.
 *
 * ⸻
 *
 * 5. Error & Exception Handling
 *     •  In preContainer(), if user-defined errorHandler / exceptionHandler exist, they are registered.
 *     •  Ensures errors and exceptions are centrally managed.
 *
 * ⸻
 *
 * 6. Extensibility Hooks
 *     •  preContainer() – allows adding helpers, error handlers, constants before container setup.
 *     •  postContainer() – injects application config values into the container.
 *     •  Can be extended to override behavior without changing core.
 *
 * ⸻
 *
 * In short:
 * Application.php is the framework bootstrapper that:
 *     •  Sets up environment and config.
 *     •  Prepares services in a DI container.
 *     •  Provides a controlled lifecycle for both HTTP and CLI execution.
 *     •  Hooks into events and error handling.
 *
 * It’s the backbone of running an Orange-based application.
 *
 * @package orange\framework
 */

class Application
{
    // Dependency Injection Container
    protected static ContainerInterface $container;
    // this classes configuration array
    protected static array $config;
    // application environmental values
    protected static array $env;
    // attached globals $_POST, $_GET, etc...
    protected static array $globals;

    /**
     * start a http application
     *
     * @param null|array $config
     * @return ContainerInterface
     * @throws InvalidValue
     * @throws DirectoryNotFound
     * @throws ConfigFileNotFound
     * @throws MissingRequired
     * @throws FileNotFound
     * @throws IncorrectInterface
     */
    public static function http(array $config = []): ContainerInterface
    {
        // call bootstrap function which returns a container
        static::bootstrap('http', $config);

        // call event
        static::$container->events->trigger('before.router', static::$container->input);

        // match uri & method to route
        static::$container->router->match(static::$container->input->requestUri(), static::$container->input->requestMethod());

        // call event
        static::$container->events->trigger('before.controller', static::$container->router, static::$container->input);

        // dispatch route
        static::$container->output->write(static::$container->dispatcher->call(static::$container->router->getMatched()));

        // call event
        static::$container->events->trigger('before.output', static::$container->router, static::$container->input, static::$container->output);

        // send header, status code and output
        static::$container->output->send();

        // call event
        static::$container->events->trigger('before.shutdown', static::$container->router, static::$container->input, static::$container->output);

        // return the container
        return static::$container;
    }

    /**
     * start a cli application
     *
     * either pass in the config directory OR let it guess (__ROOT__ . '/config')
     *
     * @return ContainerInterface
     * @throws InvalidValue
     * @throws DirectoryNotFound
     * @throws ConfigFileNotFound
     * @throws MissingRequired
     * @throws FileNotFound
     * @throws IncorrectInterface
     */
    public static function cli(array $config = []): ContainerInterface
    {
        // call bootstrap function which returns a container
        return static::bootstrap('cli', $config);
    }

    /**
     * Bootstraps the application environment
     *
     * @param string $mode
     * @return ContainerInterface
     * @throws InvalidValue
     * @throws DirectoryNotFound
     * @throws ConfigFileNotFound
     * @throws MissingRequired
     * @throws FileNotFound
     * @throws IncorrectInterface
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
        if (!realpath(__ROOT__) || !is_dir(__ROOT__)) {
            throw new DirectoryNotFound(__ROOT__);
        }

        // switch to root
        chdir(__ROOT__);

        // try to setup the environment if it hasn't been loaded already
        // this also sets the ENVIRONMENT constant
        static::loadEnvironment();

        // try to setup the application config if it hasn't been loaded already
        // this also setups up the config directories
        static::loadConfig();

        // the passed config will REPLACE anything in the loaded config by KEY
        static::$config = array_replace(static::$config, $config);

        // set DEBUG default to false (production)
        define('DEBUG', static::env('DEBUG', false));

        // config also has some additional application setup variables
        ini_set('display_errors', static::$config['display_errors']);
        ini_set('display_startup_errors', static::$config['display_startup_errors']);
        error_reporting(static::$config['error_reporting']);

        // set timezone
        date_default_timezone_set(static::$config['timezone']);

        // Set internal encoding.
        ini_set('default_charset', static::$config['encoding']);
        mb_internal_encoding(static::$config['encoding']);
        define('CHARSET', static::$config['encoding']);

        // set umask to a known state
        umask(static::$config['umask']);

        // this extension is required and now part of php 8+
        if (!extension_loaded('mbstring')) {
            throw new MissingRequired('extension: mbstring');
        }

        // default to NO character on substitute
        mb_substitute_character(static::$config['mb_substitute_character']);

        // the developer can extend this class and override these methods
        static::preContainer();
        static::$container = static::bootstrapContainer(static::getServices());
        static::postContainer();

        // return the container
        return static::$container;
    }

    /**
     * Load helper functions and setup error handlers
     *
     * @return void
     * @throws FileNotFound
     * @throws ConfigFileNotFound
     * @throws InvalidValue
     */
    protected static function preContainer(): void
    {
        // include the user supplied helpers
        $helpers = static::$config['helpers'] ?? [];
        // include the orange required helpers
        $helpers = $helpers + (static::$config['required helpers'] ?? []);

        foreach ($helpers as $helperFile) {
            // ensure the helper file exists
            if (!$helperFileRP = realpath($helperFile)) {
                throw new FileNotFound($helperFile);
            }
            // include the helper file
            include $helperFileRP;
        }

        // now errorHandler() & errorHandler() should be setup
        // try to attach the exception and error handler
        if (function_exists('errorHandler')) {
            set_error_handler('errorHandler');
        }

        if (function_exists('errorHandler')) {
            set_exception_handler('exceptionHandler');
        }

        // load the constants and apply them
        foreach (static::getCascadingConfigArray(static::findConfigFiles('constants')) as $name => $value) {
            // Constants should all be uppercase - not an option!
            $name = strtoupper($name);
            // If the constant is not already defined, define it
            if (!defined($name)) {
                define($name, $value);
            }
        }
    }

    /**
     * Initializes the DI container using service configuration
     *
     * @return ContainerInterface
     * @throws ConfigFileNotFound
     * @throws InvalidValue
     * @throws IncorrectInterface
     */
    protected static function bootstrapContainer(array $services): ContainerInterface
    {
        // make sure we have a container service
        if (!isset($services['container'])) {
            throw new InvalidValue('Container Service not found.');
        }

        // Make sure container is a Closure
        if (!$services['container'] instanceof \Closure) {
            throw new IncorrectInterface('Container services not a closure.');
        }

        // now get the empty container and save a copy in our object
        $container = $services['container']($services);

        // make sure the container is an instance of the ContainerInterface
        if (!$container instanceof ContainerInterface) {
            throw new IncorrectInterface('The service "container" did not return an object using the container interface.');
        }

        // return the container object
        return $container;
    }

    /**
     * Place holder incase you extend this class
     *
     * @return void
     */
    protected static function postContainer(): void
    {
        // finally put the application config values into the container as $application.KEY
        foreach (static::$config as $key => $value) {
            // add to container as $application.KEY
            static::$container->set('$application.' . $key, $value);
        }
    }

    /**
     * Get the globals array or a specific key from it
     *
     * @param null|string $key
     * @return mixed
     */
    public static function fromGlobals(?string $key = null): mixed
    {
        // set them if they aren't already set
        static::setGlobals();

        // if a key is provided then return that value
        // otherwise return the entire globals array
        return $key ? (static::$globals[$key] ?? null) : static::$globals;
    }

    /**
     * Set the globals array
     *
     * @param array $globals
     * @return void
     */
    public static function setGlobals(array $globals = []): void
    {
        // set defaults if they aren't already set
        if (!isset(static::$globals)) {
            // initialize the globals array
            static::$globals = [
                'post' => $_POST,
                'server' => $_SERVER,
                'cookie' => $_COOKIE,
                'request' => $_REQUEST,
                'body' => file_get_contents('php://input'),
                'files' => $_FILES,
                'php_sapi' => PHP_SAPI, // string
                'stdin' => defined('STDIN'), // boolean
            ];
        }

        // replace the globals array with the new values
        foreach ($globals as $name => $value) {
            static::$globals[$name] = $value;
        }
    }

    /**
     * Load the application environment
     *
     * @return void
     * @throws FileNotFound
     */
    public static function loadEnvironment(): void
    {
        // only apply if we haven't already setup the environmental settings
        if (!isset(static::$env)) {
            // load from the system
            static::$env = $_ENV;
            // clear this out so we don't try to read from it
            unset($_ENV);

            // Use the .env file(s) they provided as arguments
            foreach (func_get_args() as $environmentalFile) {
                if (!$environmentalFileRP = realpath($environmentalFile)) {
                    throw new FileNotFound($environmentalFile);
                }

                // parse the ini file and merge it into the env array
                $iniArray = parse_ini_file($environmentalFileRP, true, INI_SCANNER_TYPED);
                // make sure we got an array back
                if (!is_array($iniArray)) {
                    throw new InvalidConfigurationValue($environmentalFileRP . ' Invalid INI file format or empty file.');
                }
                // merge the new values in - recursive to handle sections
                static::$env = array_replace_recursive(static::$env, $iniArray);
            }

            // set ENVIRONMENT - defaults to production if not set in .env
            if (!defined('ENVIRONMENT')) {
                define('ENVIRONMENT', strtolower(static::env('ENVIRONMENT', 'production')));
            }
        }
    }

    /**
     * Setup the configuration directories
     *
     * @return void
     * @throws FileNotFound
     */
    public static function loadConfig(): void
    {
        // did we setup config already?
        if (!isset(static::$config)) {
            // get the application config array
            static::$config = static::getCascadingConfigArray(static::getApplicationConfigFiles(func_get_args()));
            // setup the config directories
            static::setupConfigDirectories();
        }
    }

    /**
     * The only place $_ENV should be and accessed
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public static function env(string $key, mixed $default = null): mixed
    {
        // Load the environment variables
        static::loadEnvironment();

        // Get the value from the environment variables
        $value = static::$env[$key] ?? $default;

        // Cast the value to the appropriate type
        if (is_string($value)) {
            $value = match (strtolower($value)) {
                'true'  => true,
                'false' => false,
                'empty' => '',
                'null'  => null,
                default => $value,
            };
        }
        // Return the value
        return $value;
    }

    /**
     * Get the service configuration array from the specified directories.
     * this is its own method so it can be overwritten easier if Application is extended
     *
     * @param array $directories
     * @return array
     * @throws ConfigFileDidNotReturnAnArray
     */
    protected static function getServices(): array
    {
        // Get the service configuration array
        return static::getCascadingConfigArray(static::findConfigFiles('services'));
    }

    /**
     * Get the cascading configuration array from the specified directories.
     *
     * @param array $files
     * @return array
     * @throws ConfigFileDidNotReturnAnArray
     */
    protected static function getCascadingConfigArray(array $files): array
    {
        // Initialize the config array
        $config = [];
        // Iterate through the directories
        foreach ($files as $file) {
            // Check if the config file exists
            if ($fileRP = realpath($file)) {
                // Include the config file
                $includedConfig = include $fileRP;
                // Check if the included config is an array
                if (!is_array($includedConfig)) {
                    throw new ConfigFileDidNotReturnAnArray($fileRP);
                }
                // replace the included config with the existing config
                $config = array_replace($config, $includedConfig);
            }
        }
        // Return the config array
        return $config;
    }

    /**
     * Build the default config file array for a specific filename
     *
     * @param string $filename
     * @return array
     * @throws FileNotFound
     */
    protected static function findConfigFiles(string $filename): array
    {
        // array of config files to load
        $configFiles = [];

        // always load the framework default config first
        static::append($configFiles, __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . $filename . '.php', true);

        // Arrays in the application.php config of ['services files'] or ['constants files'] for example
        $configKey = $filename . ' files';

        // did the user supply any config files in the application config?
        if (isset(static::$config[$configKey]) && is_array(static::$config[$configKey])) {
            // load the user supplied config files in the order they were provided
            foreach (static::$config[$configKey] as $file) {
                // these are user supplied in the config so they are required
                static::append($configFiles, $file, true);
            }
        } else {
            // these are guesses because they user did not supply them so they are not required
            static::append($configFiles, __ROOT__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . $filename . '.php', false);
            static::append($configFiles, __ROOT__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . ENVIRONMENT . DIRECTORY_SEPARATOR . $filename . '.php', false);
        }

        // return the config files found
        return $configFiles;
    }

    /**
     * Append a config file to the array if it exists
     *
     * @param array &$array
     * @param string $value
     * @param bool $required
     * @return void
     * @throws FileNotFound
     */
    protected static function append(array &$array, string $value, bool $required): void
    {
        // does the file exist?
        if (!$valueRP = realpath($value)) {
            // if the file is required and doesn't exist then throw an error
            if ($required) {
                throw new FileNotFound($value);
            }
        } else {
            // add the real path to the array
            $array[$valueRP] = $valueRP;
        }
    }

    protected static function getApplicationConfigFiles(array $userProvidedApplicationConfigFiles): array
    {
        // load environment if it hasn't been already because we need ENVIRONMENT
        static::loadEnvironment();

        // array of application config files to load
        $configFiles = [];

        // always load the framework default application config first
        static::append($configFiles, __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'application.php', true);

        // did the user provide any application config files as arguments?
        if (!empty($userProvidedApplicationConfigFiles)) {
            // if they provided any application config files then load them in the order provided
            foreach ($userProvidedApplicationConfigFiles as $file) {
                // these are user supplied in the config so they are required
                static::append($configFiles, $file, true);
            }
        } else {
            // if they didn't provide any application config files then try to guess them
            static::append($configFiles, __ROOT__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'application.php', false);
            static::append($configFiles, __ROOT__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . ENVIRONMENT . DIRECTORY_SEPARATOR . 'application.php', false);
        }

        return $configFiles;
    }

    protected static function setupConfigDirectories(): void
    {
        if (isset(static::$config['config directories'])) {
            // let's make sure they included the config directories
            if (!is_array(static::$config['config directories'])) {
                throw new ConfigFileDidNotReturnAnArray('application.config directories');
            }

            // prepend the orange framework default config directory first to allow overwriting
            array_unshift(static::$config['config directories'], __DIR__ . DIRECTORY_SEPARATOR . 'config');
        } else {
            // since this is not setup we will use the defaults
            static::$config['config directories'] = [];

            // if they didn't include the config directories then build them from the application config files
            static::append(static::$config['config directories'], __DIR__ . DIRECTORY_SEPARATOR . 'config', true);
            static::append(static::$config['config directories'], __ROOT__ . DIRECTORY_SEPARATOR . 'config', false);
            static::append(static::$config['config directories'], __ROOT__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . ENVIRONMENT, false);
        }
    }
}
