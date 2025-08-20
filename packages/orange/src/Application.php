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

class Application
{
    // Dependency Injection Container
    protected static ContainerInterface $container;
    // this classes configuration array
    protected static array $config = [];
    // application environmental values
    protected static array $env;
    // attached globals $_POST, $_GET, etc...
    protected static array $globals = [];
    // where is the configuration folder from __ROOT__
    protected static string $configDirectory = 'config';

    /**
     * start a http application
     *
     * @return ContainerInterface
     * @throws InvalidValue
     * @throws DirectoryNotFound
     * @throws ConfigFileNotFound
     * @throws MissingRequired
     * @throws FileNotFound
     * @throws IncorrectInterface
     */
    public static function http(?array $config = null): ContainerInterface
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
    public static function cli(?array $config = null): ContainerInterface
    {
        static::bootstrap('cli', $config);

        return static::$container;
    }

    /**
     * Bootstraps the application environment
     *
     * @param string $mode
     * @return void
     * @throws InvalidValue
     * @throws DirectoryNotFound
     * @throws ConfigFileNotFound
     * @throws MissingRequired
     * @throws FileNotFound
     * @throws IncorrectInterface
     */
    protected static function bootstrap(string $mode, ?array $config = null): void
    {
        // this also loads ENVIRONMENT
        static::loadEnvironment();

        // set the config array
        static::$config = $config ?? static::configFromDefault();

        static::$configDirectory = static::$config['config directory'] ?? static::$configDirectory;

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
        foreach (static::$config['helpers'] ?? [] as $helperFile) {
            $absolutePath = realpath($helperFile);

            if (!$absolutePath) {
                throw new FileNotFound($helperFile);
            }

            include $helperFile;
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
        foreach (static::loadCascadingConfig(static::getConfigFiles('constants.php')) as $name => $value) {
            // Constants should all be uppercase - not an option!
            $name = strtoupper($name);

            if (!defined($name)) {
                define($name, $value);
            }
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
        // load the services config file
        $services = static::loadCascadingConfig(static::getConfigFiles('services.php'));

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
    }

    /**
     * Place holder incase you extend this class
     *
     * @return void
     */
    protected static function postContainer(): void
    {
        // place holder incase you extend this class
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
        if (empty(static::$globals)) {
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
        if (!isset(static::$env)) {
            $environmentalFiles = func_get_args();
            // load from the system
            static::$env = $_ENV;
            // clear this out so we don't try to read from it
            unset($_ENV);
            // replace any new values in a .ini file over the previous
            foreach ($environmentalFiles as $environmentalFile) {
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

            // set ENVIRONMENT defaults to production
            if (!defined('ENVIRONMENT')) {
                define('ENVIRONMENT', strtolower(static::env('ENVIRONMENT', 'production')));
            }
        }
    }

    /**
     * Load the application configuration from the default directories
     *
     * @return array
     * @throws FileNotFound
     */
    public static function configFromDefault(): array
    {
        static::$config = static::loadCascadingConfig(static::getConfigFiles('application.php'));

        return static::$config;
    }

    /**
     * Load the application configuration from the given directories
     *
     * @return array
     * @throws FileNotFound
     */
    public static function configFrom(): array
    {
        $directories = func_get_args();

        foreach ($directories as $directory) {
            if ($path = realpath(__ROOT__ . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . 'application.php')) {
                $found[] = $path;
            }
        }

        static::$config = static::loadCascadingConfig($found);

        return static::$config;
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
        static::loadEnvironment();

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
     * Load the config files in a cascading fashion
     *
     * @param string $filename
     * @param array $baseArray
     * @return array
     */
    protected static function loadCascadingConfig(array $directories, array $array = []): array
    {
        foreach ($directories as $directory) {
            $array = array_replace($array, static::includeConfig($directory));
        }

        return $array;
    }

    /**
     * Get the absolute paths of the configuration directories
     *
     * @param null|string $filename
     * @return array
     */
    public static function getConfigDirectories(?string $directory = null): array
    {
        // this also loads ENVIRONMENT
        static::loadEnvironment();

        $directory = $directory ?? static::$configDirectory;

        return static::find([
            __DIR__ . DIRECTORY_SEPARATOR . $directory,
            __ROOT__ . DIRECTORY_SEPARATOR . $directory,
            __ROOT__ . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . ENVIRONMENT,
        ]);
    }

    /**
     * Build an array for a given config file
     *
     * @param string $filename
     * @param null|string $directory
     * @return array
     * @throws FileNotFound
     */
    protected static function getConfigFiles(string $filename, ?string $directory = null): array
    {
        // this also loads ENVIRONMENT
        static::loadEnvironment();

        $directory = $directory ?? static::$configDirectory;

        return static::find([
            __DIR__ . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $filename,
            __ROOT__ . DIRECTORY_SEPARATOR . $directory  . DIRECTORY_SEPARATOR . $filename,
            __ROOT__ . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . ENVIRONMENT . DIRECTORY_SEPARATOR . $filename,
        ]);
    }

    /**
     * Find the absolute paths of the given directories
     *
     * @param array $paths
     * @return array
     */
    protected static function find(array $paths): array
    {
        $found = [];

        foreach ($paths as $path) {
            if ($path = realpath($path)) {
                $found[] = $path;
            }
        }

        return $found;
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
    protected static function includeConfig(string $file): array
    {
        $absolutePath = realpath($file);

        if (!$absolutePath) {
            throw new FileNotFound($file);
        }

        $return = include $absolutePath;

        if (!is_array($return)) {
            throw new ConfigFileDidNotReturnAnArray($absolutePath);
        }

        return $return;
    }
}
