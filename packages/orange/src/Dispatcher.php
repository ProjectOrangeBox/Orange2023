<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\base\Singleton;
use orange\framework\exceptions\InvalidValue;
use orange\framework\interfaces\ContainerInterface;
use orange\framework\interfaces\DispatcherInterface;
use orange\framework\exceptions\dispatcher\MethodNotFound;
use orange\framework\exceptions\dispatcher\ControllerClassNotFound;

/**
 * Class Dispatcher
 * 
 * A dispatcher responsible for handling route-matched callbacks and invoking 
 * the appropriate controller methods. Implements the singleton pattern.
 * Use Singleton::getInstance() to obtain an instance.
 * 
 * @package orange\framework
 */
class Dispatcher extends Singleton implements DispatcherInterface
{
    /**
     * The dependency injection container instance.
     *
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

    /**
     * Constructor is protected to enforce singleton usage.
     * Use Singleton::getInstance() to obtain the instance.
     * 
     * @param ContainerInterface $container Dependency injection container.
     */
    protected function __construct(ContainerInterface $container)
    {
        logMsg('INFO', __METHOD__);
        $this->container = $container;
    }

    /**
     * Calls the matched route's callback with the provided arguments.
     * 
     * @param array $routeMatched An array containing route match information, including
     *                            the controller, method, arguments, and additional metadata.
     * 
     * @return string The output of the controller's method, expected to be a string.
     * 
     * @throws ControllerClassNotFound If the specified controller class does not exist.
     * @throws MethodNotFound If the specified method does not exist in the controller class.
     * @throws InvalidValue If the controller's method does not return a string.
     */
    public function call(array $routeMatched): string
    {
        logMsg('INFO', __METHOD__ . ' request uri ' . ($routeMatched['request uri'] ?? ''));
        logMsg('INFO', __METHOD__ . ' matched uri ' . ($routeMatched['matched uri'] ?? ''));
        logMsg('INFO', __METHOD__ . ' matched method ' . ($routeMatched['matched method'] ?? ''));
        logMsg('INFO', __METHOD__ . ' url ' . ($routeMatched['url'] ?? ''));
        logMsg('INFO', __METHOD__ . ' argv ' . json_encode(($routeMatched['argv'] ?? [])));
        logMsg('INFO', __METHOD__ . ' argc ' . ($routeMatched['argc'] ?? ''));
        logMsg('INFO', __METHOD__ . ' args ' . (($routeMatched['args'] ?? false) ? 'true' : 'false'));
        logMsg('INFO', __METHOD__ . ' name ' . ($routeMatched['name'] ?? ''));
        logMsg('INFO', __METHOD__ . ' controller ' . ($routeMatched['callback'][self::CONTROLLER] ?? ''));
        logMsg('INFO', __METHOD__ . ' method ' . ($routeMatched['callback'][self::METHOD] ?? ''));

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
        $output = (new $controllerClass(
            $this->container->get('config'),
            $this->container->get('input'),
            $this->container->get('output')
        ))->$method(...$matches);

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
