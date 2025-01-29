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
    protected array $services = [];
    protected array $libraries = [];
    protected array $helpers = [];

    protected array $attachedServices = [];

    public function __construct(ConfigInterface $config, InputInterface $input, OutputInterface $output)
    {
        $this->attachedServices['config'] = $config;
        $this->attachedServices['input'] = $input;
        $this->attachedServices['output'] = $output;

        $appServices = $config->get('app', 'default services', []);

        $this->loadServices($appServices);

        // path to the parent directory of the parent class
        $parentPath = dirname(dirname((new \ReflectionClass(get_class($this)))->getFileName()));

        // try to load (local to extending controller) libraries
        foreach ($this->libraries as $filename) {
            $libraryFilePath = $parentPath . '/libraries/' . $filename . '.php';

            if (!file_exists($libraryFilePath)) {
                throw new FileNotFound($libraryFilePath);
            }

            logMsg('INFO', 'INCLUDE FILE "' . $libraryFilePath . '"');

            include_once $libraryFilePath;
        }

        // try to load (local to extending controller) helpers (global functions)
        foreach ($this->helpers as $filename) {
            $helperFilePath = $parentPath . '/helpers/' . $filename . '.php';

            if (!file_exists($helperFilePath)) {
                throw new FileNotFound($helperFilePath);
            }

            logMsg('INFO', 'INCLUDE FILE "' . $helperFilePath . '"');

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

            $this->attachedServices[strtolower($key)] = container()->get($name);
        }
    }

    /**
     * This lets you use loaded services as if they were
     * attached directly to the controller
     *
     * $this->output
     *
     */
    public function __get(string $key): mixed
    {
        $key = strtolower($key);

        if (!isset($this->attachedServices[$key])) {
            throw new ServiceNotFound($key);
        }

        return $this->attachedServices[$key];
    }
}
