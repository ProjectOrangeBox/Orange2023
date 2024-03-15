<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\Singleton;
use orange\framework\exceptions\InvalidValue;
use orange\framework\exceptions\MethodNotFound;
use orange\framework\interfaces\ContainerInterface;
use orange\framework\interfaces\DispatcherInterface;
use orange\framework\exceptions\ControllerClassNotFound;

class Dispatcher extends Singleton implements DispatcherInterface
{
    private static ?DispatcherInterface $instance = null;
    protected ContainerInterface $container;

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    protected function __construct($container)
    {
        $this->container = $container;
    }

    public static function getInstance(ContainerInterface $container): self
    {
        if (self::$instance === null) {
            self::$instance = new self($container);
        }

        return self::$instance;
    }

    /* pass in whatever was in router callback as well as any arguments */
    public function call(array $routeMatched): string
    {
        logMsg('DEBUG', __METHOD__ . PHP_EOL . var_export($routeMatched, true));

        $controllerClass = $routeMatched['callback'][0];

        if (class_exists($controllerClass)) {
            $method = $routeMatched['callback'][1];

            if (method_exists($controllerClass, $method)) {
                // we found something
                $matches = array_map(function ($value) {
                    return urldecode($value);
                }, $routeMatched['argv']);

                // auto inject into controller __construct services which match the exact variable name
                // auto injection based on variable name is service name
                // PHP 8: Constructor property promotion
                // public function __construct(public OutputInterface $output,public InputInterface $input, public ConfigInterface $config,public  ViewInterface $view, public DataInterface $data)
                $services = [];

                $reflection = new \ReflectionClass($controllerClass);

                if ($reflection->getConstructor()) {
                    foreach ($reflection->getConstructor()->getParameters() as $param) {
                        // return the variable of course without the $
                        $serviceName = (string)$param->getName();
                        $services[] = $this->container->$serviceName;
                    }
                }

                $output = (new $controllerClass(...$services))->$method(...$matches);
            } else {
                throw new MethodNotFound($method);
            }
        } else {
            throw new ControllerClassNotFound($controllerClass);
        }

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
