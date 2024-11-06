<?php

declare(strict_types=1);

namespace orange\framework\interfaces;

interface ConfigInterface
{
    public function __get(string $filename): array;
    public function get(string $filename, string $key = null, mixed $defaultValue = null): mixed;
}
