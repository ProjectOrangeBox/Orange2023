<?php

declare(strict_types=1);

namespace orange\framework;

use Closure;
use orange\framework\base\Singleton;
use orange\framework\exceptions\NotFound;
use orange\framework\exceptions\InvalidValue;
use orange\framework\traits\ConfigurationTrait;
use orange\framework\interfaces\ContainerInterface;
use orange\framework\exceptions\container\ServiceNotFound;

/**
 * Container class for managing services in the application.
 *
 * This class acts as a service container that allows registering, retrieving, and managing services
 * such as closures, values, and aliases. It also supports service resolution, checking if a service exists,
 * and debugging the registered services.
 * Use Singleton::getInstance() to obtain an instance.
 *
 * @package orange\framework
 */
class Container extends Singleton implements ContainerInterface
{
    /** include ConfigurationTrait methods */
    use ConfigurationTrait;

    /**
     * List of registered services.
     */
    protected array $registeredServices = [];

    /**
     * Container constructor.
     *
     * Initializes the container and registers itself as a service.
     */
    protected function __construct()
    {
        // this will replace any previous "container" service with myself
        $this->addValue('container', $this);
    }

    /**
     * Magic method to get a service from the container.
     *
     * Allows accessing services using property syntax:
     * $foo = $container->{'$var'}; // Access service
     * $foo = $container->logger;   // Access service
     *
     * @param string $serviceName The name of the service.
     * @return mixed The service instance or value.
     * @throws ServiceNotFound If the service is not found.
     */
    public function __get(string $serviceName): mixed
    {
        return $this->get($serviceName);
    }

    /**
     * Retrieve a service from the container.
     *
     * This method handles service resolution and returns the corresponding
     * service instance, value, or closure based on the registered type.
     *
     * @param string $serviceName The name of the service.
     * @return mixed The resolved service.
     * @throws ServiceNotFound If the service is not registered.
     */
    public function get(string $serviceName): mixed
    {
        $normalizedName = $this->normalize($serviceName);

        // Get an alias for this service if one exists
        $normalizedName = $this->getAlias($normalizedName);

        // Service not registered
        if (!isset($this->registeredServices[$normalizedName])) {
            throw new ServiceNotFound($serviceName);
        }

        // Retrieve the service type and reference
        $serviceType = $this->registeredServices[$normalizedName][self::TYPE];
        $serviceReference = $this->registeredServices[$normalizedName][self::REFERENCE];

        switch ($serviceType) {
            case self::VALUE:
            case self::OBJECT:
                $service = $serviceReference;
                break;
            case self::CLOSURE:
                // Call closure passing the container as the argument
                $service = $serviceReference($this);
                break;
            default:
                throw new ServiceNotFound('Unknown Service Type: ' . $serviceType);
        }

        return $service;
    }

    /**
     * Magic method to set a service in the container.
     *
     * Allows setting services using property syntax:
     * $container->{'$var'} = 'foobar';      // Set service value
     * $container->logger = function() {};    // Set service closure
     * $container->foo = ['name'=>'johnny'];  // Set service array
     *
     * @param string $serviceName The name of the service.
     * @param mixed $value The value of the service (could be a closure, object, or value).
     */
    public function __set(string $serviceName, mixed $value): void
    {
        $this->set($serviceName, $value);
    }

    /**
     * Set a service in the container.
     *
     * This method allows registering a service as a value, closure, or alias.
     *
     * @param array|string $serviceName The service name or an array of service names.
     * @param mixed $arg The service value or closure.
     */
    public function set(array|string $serviceName, mixed $arg = null): void
    {
        if (is_array($serviceName)) {
            foreach ($serviceName as $sn => $args) {
                $this->set($sn, $args);
            }
        } else {
            if (substr($serviceName, 0, 1) == '@') {
                // If it starts with @, it is an alias
                $this->addAlias(substr($serviceName, 1), $arg);
            } elseif ($arg instanceof Closure) {
                // If it is a closure
                $this->addClosure($serviceName, $arg);
            } else {
                // Otherwise, treat it as a value
                $this->addValue($serviceName, $arg);
            }
        }
    }

    /**
     * Check if a service is registered in the container.
     *
     * @param string $serviceName The service name.
     * @return bool True if the service is registered, false otherwise.
     */
    public function __isset(string $serviceName): bool
    {
        return $this->isset($serviceName);
    }

    /**
     * Check if a service is registered in the container.
     *
     * @param string $serviceName The service name.
     * @return bool True if the service exists, false otherwise.
     */
    public function isset(string $serviceName): bool
    {
        return isset($this->registeredServices[$this->normalize($serviceName)]);
    }

    /**
     * Check if a service exists in the container.
     *
     * @param string $serviceName The service name.
     * @return bool True if the service exists, false otherwise.
     */
    public function has(string $serviceName): bool
    {
        return $this->isset($serviceName);
    }

    /**
     * Magic method to unset a service from the container.
     *
     * @param string $serviceName The service name to remove.
     */
    public function __unset(string $serviceName): void
    {
        $this->unset($serviceName);
    }

    /**
     * Remove a service from the container.
     *
     * @param string $serviceName The service name to remove.
     */
    public function unset(string $serviceName): void
    {
        unset($this->registeredServices[$this->normalize($serviceName)]);
    }

    /**
     * Remove a service from the container.
     *
     * @param string $serviceName The service name to remove.
     */
    public function remove(string $serviceName): void
    {
        $this->unset($serviceName);
    }

    /**
     * Return a debug array of the registered services.
     *
     * @return array The debug information of the registered services.
     */
    public function __debugInfo(): array
    {
        return $this->debugInfo();
    }

    /**
     * Return a debug array of the registered services.
     *
     * @return array The debug information of the registered services.
     */
    public function debugInfo(): array
    {
        $debug = [];

        foreach (array_keys($this->registeredServices) as $key) {
            $debug[$key] = $this->getServiceType($key);
        }

        return $debug;
    }

    /**
     * Get the type of a service.
     *
     * @param string $serviceName The service name.
     * @return string The service type (Closure, Alias, etc.).
     * @throws NotFound If the service type is unknown.
     */
    protected function getServiceType(string $serviceName): string
    {
        $service = $this->registeredServices[$serviceName];

        switch ($service[self::TYPE]) {
            case self::CLOSURE:
            case self::OBJECT:
                return 'Closure';
            case self::ALIAS:
                return 'Alias';
            case self::REFERENCE:
                return gettype($service[self::REFERENCE]);
            default:
                throw new NotFound('Unknown service type.');
        }
    }

    /**
     * Get all registered service names.
     *
     * @return array The list of all service names.
     */
    public function getServices(): array
    {
        return \array_keys($this->registeredServices);
    }

    /* protected */

    /**
     * Attach a service to the container.
     *
     * @param int $type The service type (e.g., VALUE, CLOSURE, ALIAS).
     * @param string $normalizedName The normalized service name.
     * @param mixed $reference The service reference (closure, value, object, etc.).
     * @return $this
     */
    protected function attach(int $type, string $normalizedName, mixed $reference): self
    {
        $this->registeredServices[$normalizedName] = [
            self::TYPE => $type,
            self::REFERENCE => $reference,
        ];

        return $this;
    }

    /**
     * Add an alias for a service.
     *
     * @param string $alias The alias name.
     * @param string $serviceName The service name.
     * @return $this
     */
    protected function addAlias(string $alias, string $serviceName): self
    {
        return $this->attach(self::ALIAS, $this->normalize($alias), $this->normalize($serviceName));
    }

    /**
     * Add a closure service to the container.
     *
     * @param string $serviceName The service name.
     * @param Closure $closure The closure to execute.
     * @return $this
     */
    protected function addClosure(string $serviceName, Closure $closure): self
    {
        return $this->attach(self::CLOSURE, $this->normalize($serviceName), $closure);
    }

    /**
     * Add a value service to the container.
     *
     * @param string $serviceName The service name.
     * @param mixed $value The value of the service.
     * @return $this
     */
    protected function addValue(string $serviceName, mixed $value): self
    {
        return $this->attach(self::VALUE, $this->normalize($serviceName), $value);
    }

    /**
     * Resolves an alias to its final reference.
     *
     * @param string $normalizedName The initial normalized name.
     * @return string The final resolved name after resolving aliases.
     * @throws InvalidValue If an alias resolution exceeds the maximum allowed depth.
     */
    protected function getAlias(string $normalizedName): string
    {
        $maxDepth = 16; // Prevent infinite loops
        $depth = 0;

        // Loop to resolve alias references
        while (
            isset($this->registeredServices[$normalizedName]) &&
            $this->registeredServices[$normalizedName][self::TYPE] === self::ALIAS
        ) {
            if ($depth >= $maxDepth) {
                throw new InvalidValue("Alias resolution exceeded maximum depth of {$maxDepth}");
            }

            $normalizedName = $this->registeredServices[$normalizedName][self::REFERENCE];
            $depth++;
        }

        return $normalizedName;
    }
}
