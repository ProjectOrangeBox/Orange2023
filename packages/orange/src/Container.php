<?php

declare(strict_types=1);

namespace orange\framework;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use ReflectionException;
use orange\framework\base\Singleton;
use orange\framework\exceptions\NotFound;
use orange\framework\exceptions\InvalidValue;
use orange\framework\base\SingletonArrayObject;
use orange\framework\traits\ConfigurationTrait;
use orange\framework\interfaces\ContainerInterface;
use orange\framework\exceptions\container\ServiceNotFound;
use orange\framework\exceptions\container\UnableToResolve;
use orange\framework\exceptions\container\FailedToAutoWire;
use orange\framework\exceptions\container\ConstructorNotPublic;

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
    protected function __construct(array $services = [])
    {
        // container is now "this" instance
        // not the closure that created this instance
        if (!$this->isset('container')) {
            $services['container'] = $this;
        }

        // send in our services
        $this->setMany($services);
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
        // normalize and get an alias for this service if one exists
        $normalizedName = $this->getAlias($this->normalize($serviceName));

        // Service not registered
        if (!isset($this->registeredServices[$normalizedName])) {
            throw new ServiceNotFound($serviceName);
        }

        // Determine how to return the service based on its type
        switch ($this->registeredServices[$normalizedName][self::TYPE]) {
            case self::VALUE:
            case self::OBJECT:
                // If this is a value or object, return it directly
                $service = $this->registeredServices[$normalizedName][self::REFERENCE];
                break;
            case self::AUTOWIRECLASS:
                // If this is a Class Name then try to autowire the arguments
                $service = $this->autoWire($normalizedName, $this->registeredServices[$normalizedName][self::REFERENCE]);
                // Convert to singleton if necessary
                $this->convertToSingleton($normalizedName, $service);
                break;
            case self::CLOSURE:
                // Call closure passing the container as the argument
                $serviceReference = $this->registeredServices[$normalizedName][self::REFERENCE];

                $service = $serviceReference($this);
                // if it is an object
                if (is_object($service)) {
                    // Convert to singleton if necessary
                    $this->convertToSingleton($normalizedName, $service);
                }
                break;
            default:
                // Handle unknown service types
                throw new ServiceNotFound('Unknown Service Type: ' . $this->registeredServices[$normalizedName][self::TYPE]);
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
    public function set(string $serviceName, mixed $arg = null): void
    {
        if (substr($serviceName, 0, 1) == '@') {
            // If it service name starts with @, it is an alias
            $this->addAlias(substr($serviceName, 1), $arg);
        } elseif (is_string($arg) && substr($arg, 0, 1) == '*') {
            // if the service value starts with * it's a fully qualified class name and should be auto wired
            $this->addAutoWireClass($serviceName, substr($arg, 1));
        } elseif ($arg instanceof Closure) {
            // If it is a closure add it as a closure service all closures get a reference to this container passed as the only argument
            $this->addClosure($serviceName, $arg);
        } else {
            // Otherwise, treat it as a value / object
            $this->addValue($serviceName, $arg);
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
        // determine if the service is registered
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
            case self::AUTOWIRECLASS:
                $isA = 'autowired fully qualifed classname';
                break;
            case self::CLOSURE:
                $isA = 'closure';
                break;
            case self::OBJECT:
                $isA = 'object';
                break;
            case self::ALIAS:
                $isA = 'alias';
                break;
            case self::REFERENCE:
                $isA = 'reference';
                break;
            case self::VALUE:
                $isA = gettype($service[self::REFERENCE]);
                break;
            default:
                throw new NotFound('Unknown service type [' . $service[self::TYPE] . '].');
        }

        return $isA;
    }

    /**
     * Attach a service to the container.
     *
     * @param int $type The service type (e.g., VALUE, CLOSURE, ALIAS).
     * @param string $normalizedName The normalized service name.
     * @param mixed $reference The service reference (closure, value, object, etc.).
     * @return Container
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
     * @return Container
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
     * @return Container
     */
    protected function addClosure(string $serviceName, Closure $closure): self
    {
        return $this->attach(self::CLOSURE, $this->normalize($serviceName), $closure);
    }

    /**
     * Add a Autowire class by fully qualified class name
     *
     * @param string $serviceName
     * @param string $className
     * @return Container
     */
    protected function addAutoWireClass(string $serviceName, string $className): self
    {
        return $this->attach(self::AUTOWIRECLASS, $this->normalize($serviceName), $className);
    }

    /**
     * Add a value service to the container.
     *
     * @param string $serviceName The service name.
     * @param mixed $value The value of the service.
     * @return Container
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
        // Prevent infinite loops
        $maxDepth = 16;
        // curernt depth of alias resolution
        $depth = 0;

        // Loop to resolve alias references
        while (isset($this->registeredServices[$normalizedName]) && $this->registeredServices[$normalizedName][self::TYPE] === self::ALIAS) {
            if ($depth >= $maxDepth) {
                throw new InvalidValue("Alias resolution exceeded maximum depth of {$maxDepth}");
            }

            $normalizedName = $this->registeredServices[$normalizedName][self::REFERENCE];
            $depth++;
        }

        return $normalizedName;
    }

    /**
     * Set multiple services at one time
     *
     * @param array $many
     * @return void
     */
    protected function setMany(array $many): void
    {
        foreach ($many as $serviceName => $args) {
            $this->set($serviceName, $args);
        }
    }

    /**
     *
     *
     * @param string $serviceName
     * @param string $class
     * @return mixed
     * @throws FailedToAutoWire
     */
    protected function autoWire(string $serviceName, string $class): mixed
    {
        $service = null;

        $classReflection = new ReflectionClass($class);

        $constructor = $classReflection->getConstructor();

        try {
            // most basic no arguments needed
            $service = ($constructor === null || $constructor->getNumberOfParameters() === 0) ? $this->withoutArguments($classReflection, $class) : $this->withArguments($classReflection, $class, $serviceName);
        } catch (ReflectionException $e) {
            throw new FailedToAutoWire($class . ' ' . $e->getMessage());
        }

        return $service;
    }

    /**
     *
     *
     * @param ReflectionClass $classReflection
     * @param string $fullyQualifiedName
     * @return mixed
     * @throws ReflectionException
     * @throws ConstructorNotPublic
     */
    protected function withoutArguments(ReflectionClass $classReflection, string $fullyQualifiedName): mixed
    {
        // try getInstance first
        if ($getInstanceMethod = $this->hasGetInstance($classReflection)) {
            $instance = $getInstanceMethod->invoke(null);
        } else {
            // then the constructor
            $instance = $this->constructorIsPublic($classReflection, $fullyQualifiedName)->newInstance();
        }

        return $instance;
    }

    /**
     *
     *
     * @param ReflectionClass $classReflection
     * @param string $fullyQualifiedName
     * @param string $serviceName
     * @return mixed
     * @throws ServiceNotFound
     * @throws ReflectionException
     * @throws UnableToResolve
     * @throws ConstructorNotPublic
     */
    protected function withArguments(ReflectionClass $classReflection, string $fullyQualifiedName, string $serviceName): mixed
    {
        // more complex we need to collect arguments
        $args = [];

        foreach ($classReflection->getConstructor()->getParameters() as $param) {
            // Get type information
            $type = $param->getType();
            // Get argument name
            $argument = $param->getName();

            if (strlen($argument) > 6 && substr($argument, 0, 6) == 'config' && (string)$type == 'array') {
                // This is special to get config service values
                $args[] = $this->get('config')->get(lcfirst(substr($argument, 6)));
            } elseif ($this->has($fullyQualifiedName . '.' . $argument)) {
                // is there a fully named space class + argument match?
                // Tester::class . '.foobar' => 'Johnny Appleseed',
                $args[] = $this->get($fullyQualifiedName . '.' . $argument);
            } elseif ($this->has($serviceName . '.' . $argument)) {
                // is there a service name + argument match?
                // 'tester.man' => 14,
                $args[] = $this->get($serviceName . '.' . $argument);
            } elseif ($this->has($argument)) {
                // is there a service that matches the argument name?
                // 'person' => 'Joey Myers'
                $args[] = $this->get($argument);
            } elseif ($param->isOptional()) {
                // is this argument optional?
                $args[] = $param->getDefaultValue() ?? null;
            } elseif ($type->allowsNull()) {
                // does this argument all null?
                $args[] = null;
            } else {
                $hint = $type ? (string)$type : 'mixed';

                throw new UnableToResolve('$' . $argument . ' (' . $hint . ') for ' . $fullyQualifiedName . '::__construct().');
            }
        }

        // try getInstance first
        if ($getInstanceMethod = $this->hasGetInstance($classReflection)) {
            $instance = $getInstanceMethod->invokeArgs(null, $args);
        } else {
            // then the constructor
            $instance =  $this->constructorIsPublic($classReflection, $fullyQualifiedName)->newInstanceArgs($args);
        }

        return $instance;
    }

    /**
     *
     *
     * @param ReflectionClass $classReflection
     * @return ReflectionMethod|null
     */
    protected function hasGetInstance(ReflectionClass $classReflection): ReflectionMethod|null
    {
        try {
            // Try to get the getInstance method
            $getInstanceMethod = $classReflection->getMethod('getInstance');
        } catch (ReflectionException $e) {
            // If the method doesn't exist, we'll just set it to null
            $getInstanceMethod = null;
        }

        return $getInstanceMethod;
    }

    /**
     *
     *
     * @param ReflectionClass $classReflection
     * @param string $fullyQualifiedName
     * @return ReflectionClass
     * @throws ConstructorNotPublic
     */
    protected function constructorIsPublic(ReflectionClass $classReflection, string $fullyQualifiedName): ReflectionClass
    {
        if (!$classReflection->getConstructor()->isPublic()) {
            throw new ConstructorNotPublic($fullyQualifiedName);
        }

        return $classReflection;
    }

    /**
     * Is this a child of the orange singleton class?
     *
     * @param mixed $instance
     * @return bool
     */
    protected function isSingleton(mixed $instance): bool
    {
        $classReflection = new ReflectionClass($instance);

        $is = false;

        while ($parent = $classReflection->getParentClass()) {
            $name = $parent->getName();

            // if they use Orange Singleton or Orange Singleton Array Object
            // then we know this is a "OOP Singleton"
            if ($name == Singleton::class || $name == SingletonArrayObject::class) {
                // bail on first because orange singleton extends factory
                $is = true;
                break;
            }

            $classReflection = $parent;
        }

        return $is;
    }

    /**
     * If this is a child of the orange singleton class then we don't need to recreate it over and over
     *
     * @param string $serviceName
     * @param object $service
     * @return void
     */
    protected function convertToSingleton(string $serviceName, object $service): void
    {
        // if this is a Singleton then convert it to an Value (the single non mutable Object)
        if ($this->isSingleton($service)) {
            $this->addValue($serviceName, $service);
        }
    }
}
