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
 * Overview of Dispatcher.php
 *
 * This file defines the Dispatcher class inside the orange\framework namespace.
 * Its job is to take a route that has already been matched (via the routing system)
 * and call the correct controller method with the right arguments.
 * It follows the singleton pattern, meaning only one dispatcher instance exists at runtime.
 *
 * ⸻
 *
 * 1. Core Purpose
 * 	•	Acts as the bridge between the router and controllers.
 * 	•	Ensures the correct controller and method are executed after a route is matched.
 * 	•	Passes along any arguments extracted from the URL.
 * 	•	Enforces that controller methods return the correct type (a string).
 *
 * ⸻
 *
 * 2. Key Components
 * 	1.	Singleton Pattern
 * 	•	Inherits from Singleton.
 * 	•	Constructor is protected → ensures it can only be instantiated via Singleton::getInstance().
 * 	2.	Dependency Injection Container
 * 	•	Holds a reference to a ContainerInterface.
 * 	•	Provides controllers with common services (config, input, output).
 * 	3.	Route Handling (call() method)
 * 	•	Accepts a $routeMatched array containing details of the matched route:
 * 	•	Controller class name.
 * 	•	Method to invoke.
 * 	•	URL arguments.
 * 	•	Metadata (URI, name, etc.).
 * 	•	Logs details of the matched route for debugging.
 * 	•	Validates that:
 * 	•	The controller class exists.
 * 	•	The method exists on that class.
 * 	4.	Controller Invocation
 * 	•	Instantiates the controller with dependencies pulled from the container.
 * 	•	Calls the specified method with decoded route arguments.
 * 	•	Validates the return value: must be a string.
 * 	•	If null → converted to empty string.
 * 	•	If not string → throws InvalidValue exception.
 *
 * ⸻
 *
 * 3. Error Handling
 *
 * The dispatcher enforces correctness by throwing exceptions when something is wrong:
 * 	•	ControllerClassNotFound → controller class missing.
 * 	•	MethodNotFound → method missing on controller.
 * 	•	InvalidValue → controller method returned something other than a string.
 *
 * This ensures failures are explicit and caught early.
 *
 * ⸻
 *
 * 4. Big Picture
 * 	•	The router decides which controller and method should handle a request.
 * 	•	The Dispatcher actually executes that controller method.
 * 	•	It also injects dependencies and enforces strict return types.
 *
 * So, Dispatcher.php is the execution engine that turns a matched route into a controller call, while enforcing framework standards.
 *
 * @package orange\framework
 */
class Dispatcher extends Singleton implements DispatcherInterface
{
    /**
     * The dependency injection container instance.
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
