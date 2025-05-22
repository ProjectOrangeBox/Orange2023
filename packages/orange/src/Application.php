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
    protected array $config;
    protected string $configDirectory;

    // Constants for file names and helper paths
    // the location of the constants file
    const ORANGECONFIGDIRECTORY = __DIR__ . '/config';
    // the name of the services php file
    const SERVICESFILENAME = 'services.php';
    // the name of the config php file
    const CONFIGFILENAME = 'config.php';
    // the name of the constant php file
    const CONSTANTFILENAME = 'constants.php';

    // load these helpers by default
    const HELPERS = [
        __DIR__ . '/helpers/wrappers.php',
        __DIR__ . '/helpers/errors.php',
        __DIR__ . '/helpers/helpers.php',
    ];
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
    protected function __construct(array $config, string $mode)
    {
        $this->config = $config;

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

        // let's make sure we have a ENV array in our config
        $this->config['ENV'] = $this->config['ENV'] ?? [];

        // set DEBUG default to false (production)
        define('DEBUG', $this->config['ENV']['DEBUG'] ?? false);

        // set ENVIRONMENT defaults to production
        define('ENVIRONMENT', strtolower($this->config['ENV']['ENVIRONMENT']) ?? 'production');

        $this->configDirectory = $this->config['config directory'] ?? null;

        // this is part of the orange framework so we know it's there and an array
        // we also can't assume this was included with the config sent in
        $this->config = $this->loadCascadingConfig(self::CONFIGFILENAME);

        // ok now set those values
        ini_set('display_errors', $this->config['display_errors']);
        ini_set('display_startup_errors', $this->config['display_startup_errors']);
        error_reporting($this->config['error_reporting']);

        // set timezone
        date_default_timezone_set($this->config['timezone']);

        // Set internal encoding.
        ini_set('default_charset', $this->config['encoding']);
        mb_internal_encoding($this->config['encoding']);
        define('CHARSET', $this->config['encoding']);

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
        foreach (array_replace($this->config['helpers'] ?? [], self::HELPERS) as $helperFile) {
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
        $this->container->set(self::CONFIGARRAYSERIVICE, $this->config);
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
        foreach (array_replace(include self::ORANGECONFIGDIRECTORY . DIRECTORY_SEPARATOR . self::CONSTANTFILENAME, $this->container->config->constants) as $name => $value) {
            // Constants should all be uppercase - not an option!
            $name = strtoupper($name);

            if (!defined($name)) {
                define($name, $value);
            }
        }
    }

    protected function loadCascadingConfig(string $filename): array
    {
        $orangeConfigFile = self::ORANGECONFIGDIRECTORY . DIRECTORY_SEPARATOR . $filename;

        $finalArray = file_exists($orangeConfigFile) ? include $orangeConfigFile : [];

        if ($this->configDirectory) {
            if (!realpath($this->configDirectory) || !is_dir($this->configDirectory)) {
                throw new DirectoryNotFound($this->configDirectory);
            }

            $userConfigFile = $this->configDirectory . DIRECTORY_SEPARATOR . $filename;
            $userArray = file_exists($userConfigFile) ? include $userConfigFile : [];

            $userConfigEnvFile = $this->configDirectory . DIRECTORY_SEPARATOR . ENVIRONMENT . DIRECTORY_SEPARATOR . $filename;
            $userEnvArray = file_exists($userConfigEnvFile) ? include $userConfigEnvFile : [];

            $finalArray = array_replace($finalArray, $userArray, $userEnvArray);
        }

        return $finalArray;
    }
}
