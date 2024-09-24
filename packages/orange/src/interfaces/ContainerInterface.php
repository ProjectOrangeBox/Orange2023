<?php

declare(strict_types=1);

namespace orange\framework\interfaces;

interface ContainerInterface
{
    public function __get(string $serviceName): mixed;
    public function get(string $serviceName): mixed;

    public function __set(string $serviceName, $arg): void;
    public function set(array|string $serviceName, mixed $arg = null): void;

    public function __isset(string $serviceName): bool;
    public function isset(string $serviceName): bool;
    public function has(string $serviceName): bool;

    public function __unset(string $serviceName): void;
    public function unset(string $serviceName): void;
    public function remove(string $serviceName): void;

    public function __debugInfo(): array;
    public function debugInfo(): array;
}
