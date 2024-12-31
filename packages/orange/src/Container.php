<?php

declare(strict_types=1);

namespace orange\framework;

use Closure;
use orange\framework\base\Singleton;
use orange\framework\exceptions\NotFound;
use orange\framework\traits\ConfigurationTrait;
use orange\framework\interfaces\ContainerInterface;
use orange\framework\exceptions\container\ServiceNotFound;

class Container extends Singleton implements ContainerInterface
{
    use ConfigurationTrait;

    protected array $registeredServices = [];

    protected function __construct()
    {
        // this will replace any previous "container" service with myself
        $this->addValue('container', $this);
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
        $serviceName = $this->normalize($serviceName);

        // does it have an alias?
        if ($this->has($serviceName) && $this->registeredServices[$serviceName][self::TYPE] == self::ALIAS) {
            $serviceName = $this->registeredServices[$serviceName][self::REFERENCE];
        }

        // is this service registered?
        if (!$this->has($serviceName)) {
            throw new ServiceNotFound($serviceName);
        }

        $service = null;

        switch ($this->registeredServices[$serviceName][self::TYPE]) {
            case self::VALUE:
            case self::CLASSVALUE:
                // just a reference to a value or what ever the heck was attached (class, array, etc...)
                $service = $this->registeredServices[$serviceName][self::REFERENCE];
                break;
            case self::CLOSURE:
                // call closure passing in instance of this (container);
                $service = $this->registeredServices[$serviceName][self::REFERENCE]($this);

                // if this is a instance of singleton then convert to a value (reference)
                if ($service instanceof Singleton) {
                    $this->registeredServices[$serviceName][self::REFERENCE] = $service;
                    $this->registeredServices[$serviceName][self::TYPE] = self::CLASSVALUE;
                }
                break;
            default:
                throw new NotFound('Unknown Service Type ' . $serviceName . ' (' . self::TYPE . ')');
        }

        return $service;
    }

    /**
     * Method __set
     *
     * $container->{'$var'} = 'foobar';
     * $container->logger = function(){};
     * $container->{'@factory'} = 'realservicename';
     * $container->foo = ['name'=>'johnny','age': 21];
     * $container->bar = name\spaced\class;
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
                // if it starts with @ it is an alias
                // '@event'=>'events',
                $this->addAlias(substr($serviceName, 1), $arg);
            } elseif ($arg instanceof Closure) {
                // if it is a closure
                $this->addClosure($serviceName, $arg);
            } else {
                // then treat it like a value
                $this->addValue($serviceName, $arg);
            }
        }
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
        return isset($this->registeredServices[$this->normalize($serviceName)]);
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
        unset($this->registeredServices[$this->normalize($serviceName)]);
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
            switch ($this->registeredServices[$key][self::TYPE]) {
                case self::CLOSURE:
                case self::CLASSVALUE:
                    $type = 'Closure';
                    break;
                case self::ALIAS:
                    $type = 'Alias';
                    break;
                case self::REFERENCE:
                    $type = gettype($this->registeredServices[$key][self::REFERENCE]);
                    break;
                default:
                    throw new NotFound('Unknown Service Type');
            }

            $debug[$key] = $type;
        }

        return $debug;
    }

    /* extra not required by interface */
    public function getServices(): array
    {
        return \array_keys($this->registeredServices);
    }

    /* protected */

    protected function attach(int $type, string $serviceName, mixed $reference): self
    {
        $this->registeredServices[$this->normalize($serviceName)] = [
            self::TYPE => $type,
            self::REFERENCE => $reference,
        ];

        return $this;
    }

    protected function addAlias(string $alias, string $serviceName): self
    {
        return $this->attach(self::ALIAS, $alias, $this->normalize($serviceName));
    }

    protected function addClosure(string $serviceName, Closure $closure): self
    {
        return $this->attach(self::CLOSURE, $serviceName, $closure);
    }

    protected function addValue(string $serviceName, mixed $value): self
    {
        return $this->attach(self::VALUE, $serviceName, $value);
    }
}
