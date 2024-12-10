<?php

declare(strict_types=1);

namespace orange\framework\controllers;

use orange\framework\supporting\DirectorySearch;
use orange\framework\exceptions\filesystem\FileNotFound;
use orange\framework\exceptions\container\ServiceNotFound;
use orange\framework\interfaces\ConfigInterface;
use orange\framework\interfaces\InputInterface;
use orange\framework\interfaces\OutputInterface;

/**
 * this is a user controller that others can extend it is not nessesary but it's nice to put commonly used code here
 */
abstract class BaseController
{
    protected array $services = [];
    protected array $attachedServices = [];

    // method to responds code
    protected array $restSuccessMap = [
        'getAll' => 200,
        'getById' => 200,
        'create' => 201,
        'update' => 202,
        'delete' => 202,
    ];

    // method to json response key
    protected array $restGetMap = [
        'getById' => 'record',
        'getAll' => 'records',
    ];

    // local (to controller) library and helper folders
    protected array $libraries = [];
    protected array $helpers = [];

    // auto injection based on variable name is service name
    public function __construct(ConfigInterface $config, InputInterface $input, OutputInterface $output)
    {
        $this->attachedServices['config'] = $config;
        $this->attachedServices['input'] = $input;
        $this->attachedServices['output'] = $output;

        $this->loadServices($config->get('app', 'default services', []));

        // path to the parent directory of the parent class
        $parentPath = dirname(dirname((new \ReflectionClass(get_class($this)))->getFileName()));

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
            $this->view->search->addDirectory($addPath, DirectorySearch::FIRST);
        }

        // call the extending controller "construct"
        $this->beforeMethodCalled();
    }

    protected function beforeMethodCalled()
    {
        // place holder override in child controller if necessary
    }

    protected function preformCRUD(string $model, string $method, array $args = [], ?string $jsonKey = null, ?int $success = -1): string
    {
        $this->data['json'] = [];
        $this->data['contentType'] = 'json';

        // throws an exception which is caught by
        // exceptionHandler(Throwable $exception): void
        // which sends output based on the exception thrown
        $results = $this->$model->$method(...$args);

        // if you get here it is a success
        $this->data['statusCode'] = ($success != -1) ? $success : $this->restSuccessMap[$method];

        // if our CRUD call returns json
        // what key should be used?
        if (isset($this->restGetMap[$method]) || $jsonKey !== null) {
            $this->data['json'][$jsonKey ?? $this->restGetMap[$method]] = $results;
        }

        // send the json response
        return $this->restResponse();
    }

    /**
     * send rest response
     * set in data object:
     *   statiusCode
     *   contentType
     *   json
     */
    public function restResponse(?int $statusCode = null, ?string $contentType = null, ?string $write = null): string
    {
        $statusCode = $statusCode ?? $this->data['statusCode'];
        $contentType = $contentType ?? $this->data['contentType'];
        $write = isset($this->data['json']) ? json_encode($this->data['json']) : $write;

        // use data statusCode, contentType & json to generate the response
        $this->output->responseCode($statusCode)->contentType($contentType)->write($write);

        return '';
    }

    protected function loadServices(array $array = []): self
    {
        $this->internalLoadServices($array);
        $this->internalLoadServices($this->services);

        return $this;
    }

    protected function internalLoadServices(array $services): void
    {
        foreach ($services as $key => $name) {
            if (!is_string($key)) {
                $key = $name;
            }

            $this->attachedServices[$key] = container()->get($name);
        }
    }

    public function __get(string $name): mixed
    {
        if (!isset($this->attachedServices[$name])) {
            throw new ServiceNotFound($name);
        }

        return $this->attachedServices[$name];
    }
}
