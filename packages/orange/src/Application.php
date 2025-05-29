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

class Application
{
    // Dependency Injection Container
    protected static ContainerInterface $container;
    // config directory passed in
    protected static string $configDirectory;
    // the application configuration array
    protected static array $app = [];
    // application environmental values
    protected static array $env;
    // save these to lazy load it when needed
    protected static array $environmentalFiles = [];

    // Constants for file names and helper paths
    // the location of the constants file
    const ORANGECONFIGDIRECTORY = __DIR__ . '/config';
    // the name of the services php file
    const SERVICESFILENAME = 'services.php';
    // the name of the constant php file
    const CONSTANTFILENAME = 'constants.php';
    // the name of the application configuration file
    const APPLICATIONCONFIGFILENAME = 'application.php';
    // the service name for the start up config values
    const CONFIGDIRECTORYSERVICE = '$configDirectory';

    /**
     * Load the application environment
     *
     * @return void
     * @throws FileNotFound
     */
    public static function loadEnvironment(): void
    {
        // save these to lazy load it when needed
        static::$environmentalFiles = func_get_args();
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
        // lazy load if necessary
        static::setEnv();

        $value = static::$env[$key] ?? $default;

        if (is_string($value)) {
            $value = match (strtolower($value)) {
                'true'  => true,
                'false' => false,
                'empty' => '',
                'null'  => null,
                default => $value,
            };
        }

        return $value;
    }

    /**
     * start a http application
     *
     * either pass in the config directory OR let it guess (__ROOT__ . '/config')
     *
     * @param null|string $configDirectory
     * @return ContainerInterface
     * @throws InvalidValue
     * @throws DirectoryNotFound
     * @throws ConfigFileNotFound
     * @throws MissingRequired
     * @throws FileNotFound
     * @throws IncorrectInterface
     */
    public static function http(?string $configDirectory = null): ContainerInterface
    {
        // call bootstrap function which returns a container
        static::bootstrap('http', $configDirectory);

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

        return static::$container;
    }

    /**
     * start a cli application
     *
     * either pass in the config directory OR let it guess (__ROOT__ . '/config')
     *
     * @param null|string $configDirectory
     * @return ContainerInterface
     * @throws InvalidValue
     * @throws DirectoryNotFound
     * @throws ConfigFileNotFound
     * @throws MissingRequired
     * @throws FileNotFound
     * @throws IncorrectInterface
     */
    public static function cli(?string $configDirectory = null): ContainerInterface
    {
        static::bootstrap('cli', $configDirectory);

        return static::$container;
    }

    /**
     * Bootstraps the application environment
     *
     * @param string $mode
     * @param null|string $configDirectory
     * @return void
     * @throws InvalidValue
     * @throws DirectoryNotFound
     * @throws ConfigFileNotFound
     * @throws MissingRequired
     * @throws FileNotFound
     * @throws IncorrectInterface
     */
    protected static function bootstrap(string $mode, ?string $configDirectory): void
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

        // if nothing sent in then guess?
        static::$configDirectory = realpath($configDirectory ?? __ROOT__ . DIRECTORY_SEPARATOR . 'config');

        if (!static::$configDirectory) {
            throw new DirectoryNotFound($configDirectory);
        }

        // lazy load if necessary
        static::setEnv();

        // set DEBUG default to false (production)
        define('DEBUG', static::env('DEBUG', false));

        // set ENVIRONMENT defaults to production
        define('ENVIRONMENT', strtolower(static::env('ENVIRONMENT', 'production')));

        // load the application configuration
        static::$app = static::loadCascadingConfig(static::APPLICATIONCONFIGFILENAME);

        // config also has some additional application setup variables
        ini_set('display_errors', static::$app['display_errors']);
        ini_set('display_startup_errors', static::$app['display_startup_errors']);
        error_reporting(static::$app['error_reporting']);

        // set timezone
        date_default_timezone_set(static::$app['timezone']);

        // Set internal encoding.
        ini_set('default_charset', static::$app['encoding']);
        mb_internal_encoding(static::$app['encoding']);
        define('CHARSET', static::$app['encoding']);

        // set umask to a known state
        umask(static::$app['umask']);

        // this extension is required and now part of php 8+
        if (!extension_loaded('mbstring')) {
            throw new MissingRequired('extension: mbstring');
        }

        // default to NO character on substitute
        mb_substitute_character(static::$app['mb_substitute_character']);

        // the developer can extend this class and override these methods
        static::preContainer();
        static::bootstrapContainer();
        static::postContainer();
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
        // load any helpers they might have loaded
        foreach (static::$app['helpers'] ?? [] as $helperFile) {
            static::include($helperFile, true);
        }

        // now errorHandler() & errorHandler() should be setup
        // try to attach the exception and error handler
        if (function_exists('errorHandler')) {
            set_error_handler('errorHandler');
        }

        if (function_exists('errorHandler')) {
            set_exception_handler('exceptionHandler');
        }
    }

    /**
     * Initializes the DI container using service configuration
     *
     * @return void
     * @throws ConfigFileNotFound
     * @throws InvalidValue
     * @throws IncorrectInterface
     */
    protected static function bootstrapContainer(): void
    {
        // load the services
        $services = static::loadCascadingConfig(self::SERVICESFILENAME);

        if (!isset($services['container'])) {
            throw new InvalidValue('Container Service not found.');
        }

        // Make sure container is a Closure
        if (!$services['container'] instanceof \Closure) {
            throw new IncorrectInterface('Container services not a closure.');
        }

        // now get the empty container and save a copy in our object
        static::$container = $services['container']($services);

        if (!static::$container instanceof ContainerInterface) {
            throw new IncorrectInterface('The service "container" did not return an object using the container interface.');
        }

        // Setup the config classes configuration
        static::$container->set(self::CONFIGDIRECTORYSERVICE, static::$configDirectory);
    }

    /**
     * Defines application constants from configuration
     *
     * @return void
     * @throws ConfigFileNotFound
     * @throws InvalidValue
     */
    protected static function postContainer(): void
    {
        // load the constants and apply them
        foreach (static::loadCascadingConfig(self::CONSTANTFILENAME) as $name => $value) {
            // Constants should all be uppercase - not an option!
            $name = strtoupper($name);

            if (!defined($name)) {
                define($name, $value);
            }
        }
    }

    /**
     * Load the config files in a cascading fashion
     *
     * @param string $filename
     * @param array $baseArray
     * @return array
     */
    protected static function loadCascadingConfig(string $filename, array $baseArray = []): array
    {
        // build our final cascading config array
        return array_replace(
            $baseArray,
            static::include(self::ORANGECONFIGDIRECTORY . DIRECTORY_SEPARATOR . $filename),
            static::include(static::$configDirectory . DIRECTORY_SEPARATOR . $filename),
            static::include(static::$configDirectory . DIRECTORY_SEPARATOR . ENVIRONMENT . DIRECTORY_SEPARATOR . $filename)
        );
    }

    /**
     * include a file and if it returns something return it
     * if it doesn't return anything then return an empty array
     *
     * @param string $file
     * @param bool $required
     * @return array|void
     * @throws FileNotFound
     */
    protected static function include(string $file, bool $required = false): array
    {
        $absolutePath = realpath($file);

        if (!$absolutePath && $required) {
            throw new FileNotFound($file);
        }

        $return = [];

        if ($absolutePath) {
            $array = include $absolutePath;

            if (is_array($array)) {
                $return = $array;
            }
        }

        return $return;
    }

    protected static function setEnv(): void
    {
        // lazy load if undefined
        if (!isset(static::$env)) {
            // load from the system
            static::$env = $_ENV;
            // clear this out so we don't try to read from it
            unset($_ENV);

            // replace any new values over the old
            foreach (static::$environmentalFiles as $environmentalFile) {
                if (!file_exists($environmentalFile)) {
                    throw new FileNotFound($environmentalFile);
                }
                // parse the ini file and merge it into the env array
                $iniArray = parse_ini_file($environmentalFile, true, INI_SCANNER_TYPED);

                if (!is_array($iniArray)) {
                    throw new InvalidConfigurationValue($environmentalFile . ' Invalid INI file format or empty file.');
                }

                static::$env = array_replace_recursive(static::$env, $iniArray);
            }
            // clear this out as well
            static::$environmentalFiles = [];
        }
    }
}
