<?php

declare(strict_types=1);

namespace peels\validate\interfaces;

interface FilterInterface
{
    public function __call(string $name, array $arguments): mixed;
    public function request(array $inputKeysRules, string $method = null): array;
    public function value(mixed $value, string|array $rules): mixed;
}
