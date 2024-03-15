<?php

declare(strict_types=1);

namespace orange\framework\controllers;

use orange\framework\exceptions\FileNotFound;
use orange\framework\interfaces\DataInterface;
use orange\framework\interfaces\InputInterface;
use orange\framework\exceptions\ServiceNotFound;
use orange\framework\interfaces\ConfigInterface;
use orange\framework\interfaces\OutputInterface;
use orange\framework\interfaces\ViewInterface;

/**
 * this is a user controller that others can extend it is not nessesary but it's nice to put commonly used code here
 */
abstract class BaseController
{
    protected array $libraries = [];
    protected array $helpers = [];
    protected array $services = [];
    protected array $attached = [];

    // auto injection based on variable name is service name
    public function __construct(public OutputInterface $response, public InputInterface $request, public ConfigInterface $config, public ViewInterface $view, public DataInterface $data)
    {
        // ** PHP 8: Constructor property promotion output, input, config, view, data

        $reflector = new \ReflectionClass(get_class($this));
        $parentPath = dirname(dirname($reflector->getFileName()));

        // try to load services
        foreach ($this->services as $name => $serviceName) {
            // throws it's own exception if service not found
            $this->attached[$name] = container()->get($serviceName);
        }

        // try to load (local to extending controller) libraries
        foreach ($this->libraries as $filename) {
            $libraryFilePath = $parentPath . '/libraries/' . $filename . '.php';

            if (!file_exists($libraryFilePath)) {
                throw new FileNotFound($libraryFilePath);
            }

            include_once $libraryFilePath;
        }

        // try to load (local to extending controller) helpers (global functions)
        foreach ($this->helpers as $filename) {
            $helperFilePath = $parentPath . '/helpers/' . $filename . '.php';

            if (!file_exists($helperFilePath)) {
                throw new FileNotFound($helperFilePath);
            }

            include_once $helperFilePath;
        }

        // add the (local to extending controller) view path
        if ($addPath = realpath($parentPath . '/views')) {
            $this->view->viewSearch->addDirectory($addPath);
        }

        // add the base controllers local view path
        if ($addPath = realpath(__DIR__ . '/../views')) {
            $this->view->viewSearch->addDirectory($addPath);
        }

        // call the extending controller "construct"
        $this->_construct();
    }

    protected function _construct()
    {
        // place holder override in child controller if necessary
    }

    // auto load services as if they are public properties with the same name as the service
    public function __get(string $name): mixed
    {
        if (!isset($this->attached[$name])) {
            throw new ServiceNotFound($name);
        }

        return $this->attached[$name];
    }

    // send rest response
    public function restResponse(): string
    {
        // use data statusCode, contentType & json to generate the response
        $this->response->responseCode($this->data['statusCode'])->contentType($this->data['contentType'])->write(json_encode($this->data['json']));

        return '';
    }
}
