<?php

declare(strict_types=1);

namespace dmyers\orange\interfaces;

interface ConfigInterface
{
    public function addPath(string $absolutePath, bool $prepend = false): self;
    public function addPaths(array $paths, bool $prepend = false): self;

    public function __get(string $filename): array;
    public function __set(string $filename, array $array): void;

    public function get(string $filename, string $key = '__#NOVALUE#__', mixed $default = null): mixed;
    public function set(string $filename, mixed $key = '__#NOVALUE#__', mixed $value = '__#NOVALUE#__'): void;

    // inject entire storage array (good if using a caching system)
    public function replace(array $storage): self;
}
