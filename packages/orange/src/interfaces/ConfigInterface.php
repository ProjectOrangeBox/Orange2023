<?php

declare(strict_types=1);

namespace dmyers\orange\interfaces;

interface ConfigInterface
{
    public function addPath(string $absPath, bool $prepend = false): self;

    public function __get(string $filename): mixed;
    public function __set(string $filename, mixed $value): void;
    public function __unset(string $filename): void;
    public function __isset(string $filename): bool;

    public function get(string $filename): mixed;
    public function set(string $filename, mixed $value): void;
    public function unset(string $filename): void;
    public function isset(string $filename): bool;
    public function __debugInfo();
}
