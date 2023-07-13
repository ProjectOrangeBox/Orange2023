<?php

declare(strict_types=1);

namespace dmyers\orange;

use dmyers\orange\exceptions\MethodNotFound;
use dmyers\orange\interfaces\OutputInterface;
use dmyers\orange\interfaces\RouterInterface;
use dmyers\orange\interfaces\DispatcherInterface;
use dmyers\orange\exceptions\ControllerClassNotFound;
use dmyers\orange\interfaces\ContainerInterface;

class Dispatcher implements DispatcherInterface
{
    private static DispatcherInterface $instance;
    protected OutputInterface $output;
    protected ContainerInterface $container;

    public function __construct(OutputInterface $output, ContainerInterface $container)
    {
        $this->output = $output;
        $this->container = $container;
    }

    public static function getInstance(OutputInterface $output, ContainerInterface $container): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($output, $container);
        }

        return self::$instance;
    }

    public function call(RouterInterface $route): OutputInterface
    {
        $route = $route->getMatched();

        $controllerClass = $route['callback'][0];

        if (class_exists($controllerClass)) {
            $method = $route['callback'][1];

            if (method_exists($controllerClass, $method)) {
                // we found something
                $matches = array_map(function ($value) {
                    return urldecode($value);
                }, $route['argv']);

                $output = (new $controllerClass($this->container))->$method(...$matches);

                if (is_string($output)) {
                    $this->output->appendOutput($output);
                }
            } else {
                throw new MethodNotFound($method);
            }
        } else {
            throw new ControllerClassNotFound($controllerClass);
        }

        return $this->output;
    }
}
