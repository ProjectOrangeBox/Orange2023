<?php

declare(strict_types=1);

namespace orange\framework\controllers;

use orange\framework\helpers\DirectorySearch;
use orange\framework\interfaces\InputInterface;
use orange\framework\interfaces\ConfigInterface;
use orange\framework\interfaces\OutputInterface;
use orange\framework\exceptions\filesystem\FileNotFound;
use orange\framework\exceptions\container\ServiceNotFound;

/**
 * this is a user controller that others can extend it is not nessesary but it's nice to put commonly used code here
 */
abstract class BaseController
{
    /**
     * This array holds the services you want to autoload and attach on instantiation.
     * It allows you to load services that are local to the extending controller.
     * It is useful for loading services that are not defined in the config.
     * The key is the attached service name (if you need something other than the actual service name), and the value is the service actual name.
     *
     * @var array
     */
    protected array $services = [];

    /**
     * This array holds the libraries you want to autoload on instantiation.
     *
     * @var array
     */
    protected array $libraries = [];

    /**
     * This array holds the helpers you want to autoload on instantiation.
     * @var array
     */
    protected array $helpers = [];

    /**
     * This array holds the services attached to the controller.
     * It allows you to access services like $this->config, $this->input, $this->output, etc.
     *
     * @var array<string, mixed>
     */
    protected array $attachedServices = [];

    /**
     * BaseController constructor.
     *
     * @param ConfigInterface $config
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws FileNotFound
     */
    public function __construct(ConfigInterface $config, InputInterface $input, OutputInterface $output)
    {
        // attach the passed services to the controller
        // this way you can access them like $this->config, $this->input, $this->output, etc.
        $this->attachedServices['config'] = $config;
        $this->attachedServices['input'] = $input;
        $this->attachedServices['output'] = $output;

        // load the services defined in the config
        $this->loadServices($config->get('application', 'default services', []));

        // path to the parent directory of the parent class
        $parentPath = dirname(dirname((new \ReflectionClass(get_class($this)))->getFileName()));

        // try to load (local to extending controller) libraries
        foreach ($this->libraries as $filename) {
            // construct the path to the library file
            // it is expected to be in the libraries directory of the parent class
            // the filename should not include the .php extension
            // e.g. if the filename is 'MyLibrary', the file should be located at
            // /path/to/orange/packages/orange/src/controllers/libraries/MyLibrary.php
            $libraryFilePath = $parentPath . '/libraries/' . $filename . '.php';

            // check if the library file exists
            if (!file_exists($libraryFilePath)) {
                throw new FileNotFound($libraryFilePath);
            }

            logMsg('INFO', 'INCLUDE FILE "' . $libraryFilePath . '"');

            // include the library file
            include_once $libraryFilePath;
        }

        // try to load (local to extending controller) helpers (global functions)
        foreach ($this->helpers as $filename) {
            $helperFilePath = $parentPath . '/helpers/' . $filename . '.php';

            if (!file_exists($helperFilePath)) {
                throw new FileNotFound($helperFilePath);
            }

            // log the inclusion of the helper file
            logMsg('INFO', 'INCLUDE FILE "' . $helperFilePath . '"');

            // include the helper file
            include_once $helperFilePath;
        }

        // add the (local to extending controller) view path
        if ($addPath = realpath($parentPath . '/views')) {
            $this->view->search->addDirectory($addPath, DirectorySearch::FIRST);
        }

        // call the extending controller "construct"
        $this->beforeMethodCalled();
    }

    protected function beforeMethodCalled() {}

    /**
     * This method allows you to load services
     * from the controller's services array or from the passed array.
     * It is useful for loading services that are not defined in the config.
     *
     * @param array $array
     * @return BaseController
     */
    protected function loadServices(array $array = []): self
    {
        $this->attachServices($array);
        $this->attachServices($this->services);

        return $this;
    }

    /**
     * This is an internal method to load services
     * It allows you to load services from the controller's
     * services array or from the passed array.
     *
     * @param array $services
     * @return void
     */
    protected function attachServices(array $services): void
    {
        // loop through the services array
        foreach ($services as $key => $name) {
            if (!is_string($key)) {
                $key = $name;
            }

            // attach the service to the controller
            // this way you can access it like $this->serviceName
            // where serviceName is the key of the service
            $this->attachService($key, $name);
        }
    }

    /**
     * This is an internal method to attach a service
     * to the controller. It allows you to attach a service
     * to the controller by its key and name.
     *
     * @param string $key
     * @param string $name
     * @return void
     * @throws ServiceNotFound
     */
    protected function attachService(string $key, string $name): void
    {
        // convert the key to lowercase to match the attached services
        // this will throw an exception if the service is not found
        $this->attachedServices[strtolower($key)] = container()->get($name);
    }

    /**
     * This lets you use loaded services as if they were
     * attached directly to the controller
     *
     * $this->output
     *
     * @param string $key
     * @return mixed
     * @throws ServiceNotFound
     */
    public function __get(string $key): mixed
    {
        // convert the key to lowercase to match the attached services
        $key = strtolower($key);

        if (!isset($this->attachedServices[$key])) {
            throw new ServiceNotFound($key);
        }

        // return the attached service
        return $this->attachedServices[$key];
    }
}
