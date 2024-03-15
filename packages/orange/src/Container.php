<?php

declare(strict_types=1);

namespace orange\framework;

use Closure;
use orange\framework\Singleton;
use orange\framework\exceptions\ServiceNotFound;
use orange\framework\interfaces\ContainerInterface;

class Container extends Singleton implements ContainerInterface
{
    private static ?ContainerInterface $instance = null;
    private static array $registeredServices = [];

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    protected function __construct()
    {
        // so this can be called directly with new
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function setServices(array $serviceArray): self
    {
        $container = $this->getInstance();

        foreach ($serviceArray as $serviceName => $option) {
            $container->set($serviceName, $option);
        }

        return $container;
    }

    /**
     * Method __get
     *
     * $foo = $container->{'$var'};
     * $foo = $container->logger;
     *
     */
    public function __get(string $serviceName): mixed
    {
        return $this->get($serviceName);
    }

    public function get(string $serviceName): mixed
    {
        // standardized name
        $serviceName = self::normalizeName($serviceName);

        // does it have an alias?
        if ($this->has($serviceName) && self::$registeredServices[$serviceName]['type'] == self::ALIAS) {
            $serviceName = self::$registeredServices[$serviceName]['reference'];
        }

        // is this service registered?
        if (!$this->has($serviceName)) {
            throw new ServiceNotFound($serviceName);
        }

        return (self::$registeredServices[$serviceName]['type'] == self::VALUE) ? self::$registeredServices[$serviceName]['reference'] : self::$registeredServices[$serviceName]['reference']($this);
    }

    /**
     * Method __set
     *
     * $container->{'$var'} = 'foobar;
     * $container->logger = function(){};
     * $container->{'@factory'} = 'realservicename';
     */
    public function __set(string $serviceName, $arg): void
    {
        $this->set($serviceName, $arg);
    }

    public function set(string $serviceName, $arg): void
    {
        if (substr($serviceName, 0, 1) == '@') {
            $this->addAlias(substr($serviceName, 1), $arg);
        } else {
            if ($arg instanceof Closure) {
                $this->addClosure($serviceName, $arg);
            } else {
                $this->addValue($serviceName, $arg);
            }
        }
    }

    public function addAlias(string $alias, string $serviceName): self
    {
        return $this->attach(self::ALIAS, $alias, self::normalizeName($serviceName));
    }

    public function addClosure(string $serviceName, Closure $closure): self
    {
        return $this->attach(self::CLOSURE, $serviceName, $closure);
    }

    public function addValue(string $serviceName, mixed $value): self
    {
        return $this->attach(self::VALUE, $serviceName, $value);
    }

    public function getServices(): array
    {
        return \array_keys(self::$registeredServices);
    }

    /**
     * Check whether the Service been registered
     */
    public function __isset(string $serviceName): bool
    {
        return $this->isset($serviceName);
    }

    public function isset(string $serviceName): bool
    {
        return isset(self::$registeredServices[self::normalizeName($serviceName)]);
    }

    public function has(string $serviceName): bool
    {
        return $this->isset($serviceName);
    }

    /**
     * Remove a service
     */
    public function __unset(string $serviceName): void
    {
        $this->unset($serviceName);
    }

    public function unset(string $serviceName): void
    {
        unset(self::$registeredServices[self::normalizeName($serviceName)]);
    }

    public function remove(string $serviceName): void
    {
        $this->unset($serviceName);
    }

    /**
     * Return Debug Array
     */
    public function __debugInfo(): array
    {
        return $this->debugInfo();
    }

    public function debugInfo(): array
    {
        $debug = [];

        foreach (\array_keys(self::$registeredServices) as $key) {
            if (self::$registeredServices[$key]['type'] == self::CLOSURE) {
                $type = 'Closure';
            } elseif (self::$registeredServices[$key]['type'] == self::ALIAS) {
                $type = 'Alias';
            } else {
                $type = gettype(self::$registeredServices[$key]['reference']);
            }

            $debug[$key] = $type;
        }

        return $debug;
    }

    /**
     * Normalize the event name
     */
    protected static function normalizeName(string $name): string
    {
        return mb_convert_case($name, MB_CASE_LOWER, mb_detect_encoding($name));
    }

    protected function attach(int $type, string $serviceName, mixed $reference): self
    {
        self::$registeredServices[self::normalizeName($serviceName)] = [
            'type' => $type,
            'reference' => $reference,
        ];

        return $this;
    }
}
