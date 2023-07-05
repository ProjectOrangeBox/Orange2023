<?php

declare(strict_types=1);

namespace dmyers\orange\interfaces;

interface ConfigInterface
{
    public function addPath(string $absPath, bool $prepend = false): self;

    public function __get(string $filename): mixed;
    public function __set(string $filename, mixed $value): void;
    
    public function get(string $filename): mixed;
    public function set(string $filename, mixed $value): void;

    public function __debugInfo();
}
