<?php

declare(strict_types=1);

namespace dmyers\orange;

use dmyers\orange\exceptions\MethodNotFound;
use dmyers\orange\interfaces\RouterInterface;
use dmyers\orange\interfaces\DispatcherInterface;
use dmyers\orange\exceptions\ControllerClassNotFound;
use dmyers\orange\exceptions\InvalidValue;
use dmyers\orange\interfaces\ContainerInterface;

class Dispatcher implements DispatcherInterface
{
    private static DispatcherInterface $instance;
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public static function getInstance(ContainerInterface $container): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($container);
        }

        return self::$instance;
    }

    public function call(RouterInterface $route): string
    {
        $routeMatched = $route->getMatched();

        $controllerClass = $routeMatched['callback'][0];

        if (class_exists($controllerClass)) {
            $method = $routeMatched['callback'][1];

            if (method_exists($controllerClass, $method)) {
                // we found something
                $matches = array_map(function ($value) {
                    return urldecode($value);
                }, $routeMatched['argv']);

                // auto inject into controller construct services
                $services = [];

                $reflection = new \ReflectionClass($controllerClass);

                foreach ($reflection->getConstructor()->getParameters() as $param) {
                    $serviceName = (string)$param->getName();
                    $services[] = $this->container->$serviceName;
                }

                $output = (new $controllerClass(...$services))->$method(...$matches);
            } else {
                throw new MethodNotFound($method);
            }
        } else {
            throw new ControllerClassNotFound($controllerClass);
        }

        if ($output === null) {
            $output = '';
        } elseif (!is_string($output)) {
            throw new InvalidValue('Controller "' . $controllerClass . '" method "' . $method . '" did not return a string.');
        }

        return $output;
    }

    public function __debugInfo(): array
    {
        return [];
    }
}
