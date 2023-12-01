<?php

declare(strict_types=1);

namespace peels\validate\interfaces;

interface FilterInterface
{
    public function __call(string $name, array $arguments): mixed;
    
    public function __get(string $name);
    public function withRules($rules): self;
    public function withDefault($default): self;

    /* filter input $filtered = $this->input($rawInput,'is_string|alpha'); */
    public function input(mixed $value, string $filter): mixed;
}
