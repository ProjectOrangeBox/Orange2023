<?php

declare(strict_types=1);

namespace orange\framework;

use Closure;
use orange\framework\interfaces\ContainerInterface;
use orange\framework\exceptions\container\ServiceNotFound;

class Container implements ContainerInterface
{
    private static ?ContainerInterface $instance;

    protected array $registeredServices;

    protected function __construct()
    {
        $this->registeredServices = [];
    }
    /**
     * Only setup services if they are sent in during creation
     * If you don't then you need to use set(...)
     */
    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            // if it isn't a array then make it an array
            self::$instance = new self();
        }

        return self::$instance;
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
        if ($this->has($serviceName) && $this->registeredServices[$serviceName][self::TYPE] == self::ALIAS) {
            $serviceName = $this->registeredServices[$serviceName][self::REFERENCE];
        }

        // is this service registered?
        if (!$this->has($serviceName)) {
            throw new ServiceNotFound($serviceName);
        }

        return ($this->registeredServices[$serviceName][self::TYPE] == self::VALUE) ? $this->registeredServices[$serviceName][self::REFERENCE] : $this->registeredServices[$serviceName][self::REFERENCE]($this);
    }

    /**
     * Method __set
     *
     * $container->{'$var'} = 'foobar';
     * $container->logger = function(){};
     * $container->{'@factory'} = 'realservicename';
     * $container->foo = ['name'=>'johnny','age': 21];
     */
    public function __set(string $serviceName, $arg): void
    {
        $this->set($serviceName, $arg);
    }

    public function set(array|string $serviceName, mixed $arg = null): void
    {
        if (is_array($serviceName)) {
            foreach ($serviceName as $sn => $args) {
                $this->set($sn, $args);
            }
        } else {
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
        return \array_keys($this->registeredServices);
    }

    /**
     * Check whether the Service has been registered
     */
    public function __isset(string $serviceName): bool
    {
        return $this->isset($serviceName);
    }

    public function isset(string $serviceName): bool
    {
        return isset($this->registeredServices[self::normalizeName($serviceName)]);
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
        unset($this->registeredServices[self::normalizeName($serviceName)]);
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

        foreach (\array_keys($this->registeredServices) as $key) {
            if ($this->registeredServices[$key][self::TYPE] == self::CLOSURE) {
                $type = 'Closure';
            } elseif ($this->registeredServices[$key][self::TYPE] == self::ALIAS) {
                $type = 'Alias';
            } else {
                $type = gettype($this->registeredServices[$key][self::REFERENCE]);
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
        $this->registeredServices[self::normalizeName($serviceName)] = [
            self::TYPE => $type,
            self::REFERENCE => $reference,
        ];

        return $this;
    }
}
