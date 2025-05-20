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

    // Constants for file names and helper paths
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

    public static function __callStatic($name, $arguments): ContainerInterface
    {
        return (new static($arguments[0], $name))->container;
    }

    /**
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
                throw new InvalidValue('Unknown Run Mode "' . $mode . '".');
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
        if (!is_dir(__ROOT__)) {
            throw new DirectoryNotFound(__ROOT__);
        }

        // switch to root
        chdir(__ROOT__);

        // set DEBUG default to false (production)
        define('DEBUG', $_ENV['DEBUG'] ?? false);

        // this is part of the orange framework so we know it's there and an array
        // we also can't assume this was included with the config sent in
        $this->config = array_replace($this->include(self::ORANGECONFIGFILE, true), $this->config);

        // set ENVIRONMENT defaults to production
        define('ENVIRONMENT', strtolower($_ENV['ENVIRONMENT']) ?? 'production');

        // get our error handling defaults for the different environment types
        // these can be overridden in the passed $config array
        $envErrorsConfig = $this->config['environment errors config'][ENVIRONMENT] ?? $this->config['environment errors config']['default'];

        // ok now set those values
        ini_set('display_errors', $envErrorsConfig['display errors']);
        ini_set('display_startup_errors', $envErrorsConfig['display startup errors']);
        error_reporting($envErrorsConfig['error reporting']);

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
        $this->postContainer($this->bootstrapContainer($this->preContainer()));
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

            $this->include($helperFile, true, false);
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
        $configDirectory = $this->config['config directory'] ?? UNDEFINED;

        // final services array
        $services = array_replace(
            $this->include(self::ORANGESERVICESFILE, true),
            $this->include($configDirectory . DIRECTORY_SEPARATOR . self::SERVICESFILENAME, false),
            $this->include($configDirectory . DIRECTORY_SEPARATOR . ENVIRONMENT . DIRECTORY_SEPARATOR . self::SERVICESFILENAME, false)
        );

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
        foreach (array_replace($this->include(self::ORANGECONSTANTSFILE, true), $this->container->config->constants) as $name => $value) {
            // Constants should all be uppercase - not an option!
            $name = strtoupper($name);

            if (!defined($name)) {
                define($name, $value);
            }
        }
    }

    /**
     * Includes and optionally validates a config file
     *
     * @param string $configFilePath
     * @param bool $required
     * @param bool $isArray
     * @return array|null
     * @throws ConfigFileNotFound
     * @throws InvalidValue
     */
    protected function include(string $configFilePath, bool $required = false, bool $isArray = true): array|null
    {
        $loadedConfig = [];

        $absoluteConfigFile = realpath($configFilePath);

        if ($absoluteConfigFile === false && $required) {
            throw new ConfigFileNotFound($configFilePath);
        }

        if (is_string($absoluteConfigFile)) {
            $loadedConfig = require $absoluteConfigFile;

            if ($isArray && !is_array($loadedConfig)) {
                throw new InvalidValue('File "' . $configFilePath . '" did not return an array.');
            }
        }

        return $isArray ? $loadedConfig : null;
    }
}
