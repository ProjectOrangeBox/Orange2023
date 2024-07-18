<?php

declare(strict_types=1);

namespace orange\framework\interfaces;

use Closure;

interface ContainerInterface
{
    public const CLOSURE = 1;
    public const ALIAS = 2;
    public const VALUE = 3;
    public const TYPE = 1;
    public const REFERENCE = 2;

    // just the names
    public function getServices(): array;

    public function __get(string $serviceName): mixed;
    public function get(string $serviceName): mixed;

    public function __set(string $serviceName, $arg): void;
    public function set(array|string $serviceName, mixed $arg = null): void;

    public function addAlias(string $alias, string $serviceName): self;
    public function addClosure(string $serviceName, Closure $closure): self;
    public function addValue(string $serviceName, mixed $value): self;

    public function __isset(string $serviceName): bool;
    public function isset(string $serviceName): bool;
    public function has(string $serviceName): bool;

    public function __unset(string $serviceName): void;
    public function unset(string $serviceName): void;
    public function remove(string $serviceName): void;

    public function __debugInfo(): array;
    public function debugInfo(): array;
}
