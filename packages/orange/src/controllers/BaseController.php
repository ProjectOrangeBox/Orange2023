<?php

declare(strict_types=1);

namespace orange\framework\controllers;

use orange\framework\DirectorySearch;
use orange\framework\interfaces\DataInterface;
use orange\framework\interfaces\ViewInterface;
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
    // services by name
    protected array $services = [];

    // local (to controller) library and helper folders
    protected array $libraries = [];
    protected array $helpers = [];

    // internal
    protected array $attached = [];

    // auto injection based on variable name is service name
    public function __construct(
        public ConfigInterface $config,
        public InputInterface $request,
        public OutputInterface $response,
        public DataInterface $data,
        public ViewInterface $view
    ) {
        // ** PHP 8: Constructor property promotion output, input, config, view, data

        $reflector = new \ReflectionClass(get_class($this));
        $parentPath = dirname(dirname($reflector->getFileName()));

        // try to load services
        foreach ($this->services as $name => $serviceName) {
            $name = is_int($name) ? $serviceName : $name;

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
            $this->view->search->addDirectory($addPath, DirectorySearch::FIRST);
        }

        // call the extending controller "construct"
        $this->beforeMethodCalled();
    }

    protected function beforeMethodCalled()
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

    /**
     * send rest response
     * set in data object:
     *   statiusCode
     *   contentType
     *   json
     */
    public function restResponse(): string
    {
        // use data statusCode, contentType & json to generate the response
        $this->response->responseCode($this->data['statusCode'])->contentType($this->data['contentType'])->write(json_encode($this->data['json']));

        return '';
    }

    protected function preformCRUD(string $model, string $method, array $args = []): string
    {
        $successMap = [
            'getAll' => 200,
            'getById' => 200,
            'create' => 201,
            'update' => 202,
            'delete' => 202,
        ];

        $getMap = [
            'getById' => 'record',
            'getAll' => 'records',
        ];

        $this->data['json'] = [];
        $this->data['contentType'] = 'json';

        // throws an exception which is caught and sent as output
        $results = $this->$model->$method(...$args);

        // if you get here it is a success
        $this->data['statusCode'] = $successMap[$method];

        if (isset($getMap[$method])) {
            $this->data['json'][$getMap[$method]] = $results;
        }

        return $this->restResponse();
    }
}
