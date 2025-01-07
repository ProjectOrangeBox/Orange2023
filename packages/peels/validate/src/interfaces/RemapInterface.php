<?php

declare(strict_types=1);

namespace peels\validate\interfaces;

interface RemapInterface
{
    public function request(string $method, array|string $mapping): array;
    public function array(array $array, array|string $mapping): array;
}
