<?php

declare(strict_types=1);

namespace dmyers\orange\interfaces;

interface ConfigInterface
{
    public function addPath(string $absolutePath, bool $prepend = false): self;

    public function __get(string $filename): array;
    public function __set(string $filename, array $value): void;
    
    public function get(string $filename, mixed $key = NOVALUE): mixed;
    public function set(string $filename, mixed $key, mixed $value = NOVALUE): void;

    public function __debugInfo(): array;
}
