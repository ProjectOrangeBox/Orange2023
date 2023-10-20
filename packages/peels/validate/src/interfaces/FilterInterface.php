<?php

declare(strict_types=1);

namespace peels\validate\interfaces;

interface FilterInterface
{
    public function post(string $name = null, string $filters = '', $default = null): mixed;
    public function get(string $name = null, string $filters = '', $default = null): mixed;
    public function request(string $name = null, string $filters = '', $default = null): mixed;
    public function server(string $name = null, string $filters = '', $default = null): mixed;
    public function file(string $name = null, string $filters = '', $default = null): mixed;
    public function cookie(string $name = null, string $filters = '', $default = null): mixed;

    public function addRule(string $name, string $class): self;
    public function addRules(array $rules): self;

    /* filter input $filtered = $this->input($rawInput,'is_string|alpha'); */
    public function input(mixed $value, string $filter): mixed;

    public function remapInput(string $method, string $mapping): array;
    public function remap(array $input, string $mapping): array;
}
