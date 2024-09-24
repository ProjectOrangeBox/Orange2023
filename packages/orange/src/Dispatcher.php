<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\exceptions\InvalidValue;
use orange\framework\interfaces\ContainerInterface;
use orange\framework\interfaces\DispatcherInterface;
use orange\framework\exceptions\dispatcher\MethodNotFound;
use orange\framework\exceptions\dispatcher\ControllerClassNotFound;

class Dispatcher extends Singleton implements DispatcherInterface
{
    protected ContainerInterface $container;

    protected const CONTROLLER = 0;
    protected const METHOD = 1;

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    protected function __construct($container)
    {
        $this->container = $container;
    }

    /* pass in whatever was in router callback as well as any arguments */
    public function call(array $routeMatched): string
    {
        log::msg('DEBUG', __METHOD__ . PHP_EOL . var_export($routeMatched, true));

        // get the controller & method from the route matched
        $controllerClass = $routeMatched['callback'][self::CONTROLLER];
        $method = $routeMatched['callback'][self::METHOD];

        // get arguments
        $matches = [];

        foreach ($routeMatched['argv'] as $value) {
            $matches[] = urldecode($value);
        }

        // let's make sure the controller is present
        if (!class_exists($controllerClass)) {
            throw new ControllerClassNotFound($controllerClass);
        }

        // let's make sure the controller has this method
        if (!method_exists($controllerClass, $method)) {
            throw new MethodNotFound($controllerClass . '::' . $method);
        }

        // ok now instantiate the class and call the method
        $output = (new $controllerClass())->$method(...$matches);

        // if they didn't return anything set output to an empty string
        if ($output === null) {
            $output = '';
        } elseif (!is_string($output)) {
            // they returned something other than a string which is what the method and the output service expects so throw an error
            throw new InvalidValue('Controller "' . $controllerClass . '" method "' . $method . '" did not return a string.');
        }

        return $output;
    }
}
