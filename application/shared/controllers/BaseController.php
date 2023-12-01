<?php

declare(strict_types=1);

namespace application\shared\controllers;

use dmyers\orange\exceptions\FileNotFound;
use dmyers\orange\interfaces\DataInterface;
use dmyers\orange\interfaces\InputInterface;
use dmyers\orange\exceptions\ServiceNotFound;
use dmyers\orange\interfaces\ConfigInterface;
use dmyers\orange\interfaces\OutputInterface;
use dmyers\orange\interfaces\ViewerInterface;

/**
 * this is a user controller that others can extend it is not nessesary but it's nice to put commonly used code here
 */
abstract class BaseController
{
    protected string $contentType = '';
    protected array $libraries = [];
    protected array $services = [];

    protected array $attached = [];

    // auto injection based on variable name is service name
    public function __construct(public OutputInterface $response, public InputInterface $request, public ConfigInterface $config, public  ViewerInterface $view, public DataInterface $data)
    {
        // ** PHP 8: Constructor property promotion output, input, config, view, data

        $reflector = new \ReflectionClass(get_class($this));
        define('__CHILDDIR__', dirname(dirname($reflector->getFileName())));

        // change content type if provided
        if (!empty($this->contentType)) {
            $this->response->contentType($this->contentType);
        }

        foreach ($this->services as $name => $serviceName) {
            // throws it's own exception if service not found
            $this->attached[$name] = container()->get($serviceName);
        }

        foreach ($this->libraries as $filename) {
            $libraryFilePath = __CHILDDIR__ . '/libraries/' . $filename . '.php';

            if (!file_exists($libraryFilePath)) {
                throw new FileNotFound($libraryFilePath);
            }

            include $libraryFilePath;
        }

        // add this base controllers local views path
        $this->view->addPath(__CHILDDIR__ . '/views');

        // add the child files view path
        $this->view->addPath(__DIR__ . '/../views');

        $this->init();
    }

    public function init() {
        // place holder override in child if nessesary
    }

    public function __get(string $name): mixed
    {
        if (!isset($this->attached[$name])) {
            throw new ServiceNotFound($name);
        }

        return $this->attached[$name];
    }
}
