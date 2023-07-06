<?php

declare(strict_types=1);

namespace dmyers\orange;

use dmyers\orange\exceptions\MethodNotFound;
use dmyers\orange\interfaces\InputInterface;
use dmyers\orange\interfaces\ConfigInterface;
use dmyers\orange\interfaces\OutputInterface;
use dmyers\orange\interfaces\RouterInterface;
use dmyers\orange\interfaces\DispatcherInterface;
use dmyers\orange\exceptions\ControllerClassNotFound;

class Dispatcher implements DispatcherInterface
{
    private static DispatcherInterface $instance;
    protected InputInterface $input;
    protected OutputInterface $output;
    protected ConfigInterface $config;

    public function __construct(InputInterface $input, OutputInterface $output, ConfigInterface $config)
    {
        $this->input = $input;
        $this->output = $output;
        $this->config = $config;
    }

    public static function getInstance(InputInterface $input, OutputInterface $output, ConfigInterface $config): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($input, $output, $config);
        }

        return self::$instance;
    }

    public function call(RouterInterface $route): OutputInterface
    {
        $route = $route->getMatched();

        $controllerClass = $route['controller'];

        if (class_exists($controllerClass)) {
            $method = $route['method'];

            if (method_exists($controllerClass, $method)) {
                // we found something
                $matches = array_map(function ($value) {
                    return urldecode($value);
                }, $route['argv']);

                $output = (new $controllerClass($this->input, $this->output, $this->config))->$method(...$matches);

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
