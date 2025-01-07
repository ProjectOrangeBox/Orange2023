<?php

declare(strict_types=1);

namespace peels\validate\interfaces;

interface FilterInterface
{
    /**
     * this creates a input key passthru
     *
     * $value = $filterService->post('name','visible|length[32]');
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed;

    /**
     * filter the entire input array
     *
     * @param array $inputKeysRules
     * @param string|null $method
     * @return array
     */
    public function request(array $inputKeysRules, string $method = null): array;

    /**
     * $value = $filterService->value('abc123','visible|length[32]');
     * $value = $filterService->value('abc123',['visible','length[32]']);
     *
     * @param mixed $value
     * @param string|array $rules
     * @return mixed
     */
    public function value(mixed $value, string|array $rules): mixed;
}
