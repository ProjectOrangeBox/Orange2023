<?php

declare(strict_types=1);

namespace peels\collector;

interface CollectorInterface
{
    public function add(array|string $arg1, string|array|null $arg2 = null): self;

    public function has(array|string|null $arg1 = null): bool;
    public function hasOne(array|string $arg1): bool;

    public function remove(array|string|null $arg1 = null): self;
    public function removeAll(): self;

    public function asArray(): self;
    public function asHtml(string $between = '', string $prefix = '', string $suffix = '', string $betweenPrefix = '', string $betweenSuffix = ''): self;
    public function asJson(int $flags = 0): self;

    public function collect(array|string|null $arg1 = null): array|string;
    public function collectAll(): array|string;
}
