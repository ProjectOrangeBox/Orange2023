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

class Application
{
    // Dependency Injection Container
    public ContainerInterface $container;
    // config array passed in
    protected static string $configDirectory;
    // the application configuration array
    protected static array $app = [];
    // application environmental values
    protected static array $env = [];

    // Constants for file names and helper paths
    // the location of the constants file
    const ORANGECONFIGDIRECTORY = __DIR__ . '/config';
    // the name of the services php file
    const SERVICESFILENAME = 'services.php';
    // the name of the config php file
    const CONFIGFILENAME = 'config.php';
    // the name of the constant php file
    const CONSTANTFILENAME = 'constants.php';
    // the name of the application configuration file
    const APPLICATIONCONFIGFILENAME = 'application.php';
    // the service name for the start up config values
    const CONFIGARRAYSERIVICE = '$config';

    // this is used to setup the different static run modes
    // Application::http(['config directory' => __ROOT__ . '/config']);
    public static function __callStatic($name, $arguments): ContainerInterface
    {
        return (new static($arguments[0], $name))->container;
    }

    /**
     * You can extend this class and add more modes
     *
     * @param array $config
     * @param string $mode
     * @return void
     * @throws InvalidValue
     * @throws DirectoryNotFound
     * @throws ConfigFileNotFound
     * @throws MissingRequired
     * @throws FileNotFound
     * @throws IncorrectInterface
     */
    protected function __construct(string $configDirectory, string $mode)
    {
        // all you need to send in is the config directory
        if (!realpath($configDirectory)) {
            throw new DirectoryNotFound($configDirectory);
        }

        static::$configDirectory = $configDirectory;

        switch ($mode) {
            case 'cli':
                $this->bootstrap('cli');
                break;
            case 'http':
                // call bootstrap function which returns a container
                $this->bootstrap('http');

                // call event
                $this->container->events->trigger('before.router', $this->container->input);

                // match uri & method to route
                $this->container->router->match($this->container->input->requestUri(), $this->container->input->requestMethod());

                // call event
                $this->container->events->trigger('before.controller', $this->container->router, $this->container->input);

                // dispatch route
                $this->container->output->write($this->container->dispatcher->call($this->container->router->getMatched()));

                // call event
                $this->container->events->trigger('before.output', $this->container->router, $this->container->input, $this->container->output);

                // send header, status code and output
                $this->container->output->send();

                // call event
                $this->container->events->trigger('before.shutdown', $this->container->router, $this->container->input, $this->container->output);
                break;
            default:
                throw new InvalidValue('Unknown Application Run Mode "' . $mode . '".');
        }
    }

    /**
     * Load the application environment
     *
     * @param string $path
     * @return void
     */
    public static function load(string $path): void
    {
        static::$env = realpath($path) ? array_replace_recursive($_ENV, parse_ini_file($path, true, INI_SCANNER_TYPED)) : $_ENV;

        // clear this out so we don't try to read from it
        unset($_ENV);
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
    protected function bootstrap(string $mode): void
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

        // set DEBUG default to false (production)
        define('DEBUG', static::env('DEBUG', false));

        // set ENVIRONMENT defaults to production
        define('ENVIRONMENT', strtolower(static::env('ENVIRONMENT', 'production')));

        // Since Config can't load it's own config array we need to do it manually
        // and later attach it as a service that can be injected in when the config service is created
        static::$app = $this->loadCascadingConfig(static::APPLICATIONCONFIGFILENAME);

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
        umask(0000);

        // this extension is required and now part of php 8+
        if (!extension_loaded('mbstring')) {
            throw new MissingRequired('extension: mbstring');
        }

        // default to NO character on substitute
        mb_substitute_character('none');

        // the developer can extend this class and override these methods
        $this->preContainer();
        $this->bootstrapContainer();
        $this->postContainer();
    }

    /**
     * Load helper functions and setup error handlers
     *
     * @return void
     * @throws FileNotFound
     * @throws ConfigFileNotFound
     * @throws InvalidValue
     */
    protected function preContainer(): void
    {
        // load any helpers they might have loaded
        $helperFiles = static::$app['helpers'] ?? [];

        foreach ($helperFiles as $helperFile) {
            if (!file_exists($helperFile)) {
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
    }

    /**
     * Initializes the DI container using service configuration
     *
     * @return void
     * @throws ConfigFileNotFound
     * @throws InvalidValue
     * @throws IncorrectInterface
     */
    protected function bootstrapContainer(): void
    {
        $services = $this->loadCascadingConfig(self::SERVICESFILENAME);

        if (!isset($services['container'])) {
            throw new InvalidValue('Container Service not found.');
        }

        // Make sure container is a Closure
        if (!$services['container'] instanceof \Closure) {
            throw new IncorrectInterface('Container services not a closure.');
        }

        // now get the empty container and save a copy in our object
        $this->container = $services['container']($services);

        if (!$this->container instanceof ContainerInterface) {
            throw new IncorrectInterface('The service "container" did not return an object using the container interface.');
        }

        // add our configuration
        $this->container->set(self::CONFIGARRAYSERIVICE, $this->loadCascadingConfig(static::CONFIGFILENAME));
    }

    /**
     * Defines application constants from configuration
     *
     * @return void
     * @throws ConfigFileNotFound
     * @throws InvalidValue
     */
    protected function postContainer(): void
    {
        foreach ($this->loadCascadingConfig(self::CONSTANTFILENAME) as $name => $value) {
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
    protected function loadCascadingConfig(string $filename, array $baseArray = []): array
    {
        // we already know this is there
        $orangeConfigFile = self::ORANGECONFIGDIRECTORY . DIRECTORY_SEPARATOR . $filename;
        $orangeConfigArray = include $orangeConfigFile;

        // do we have a matching user config file?
        $userConfigFile = static::$configDirectory . DIRECTORY_SEPARATOR . $filename;
        $userConfigArray = file_exists($userConfigFile) ? include $userConfigFile : [];

        // do we have a matching user environmental config file
        $userEnvConfigFile = static::$configDirectory . DIRECTORY_SEPARATOR . ENVIRONMENT . DIRECTORY_SEPARATOR . $filename;
        $userEnvConfigArray = file_exists($userEnvConfigFile) ? include $userEnvConfigFile : [];

        // build our final cascading config array
        return array_replace($baseArray, $orangeConfigArray, $userConfigArray, $userEnvConfigArray);
    }
}
