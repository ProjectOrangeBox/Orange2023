<?php

declare(strict_types=1);

namespace peels\acl\interfaces;

interface ModelInterface
{
    public function create(array $columns): int;
    public function read(int $id): array;
    public function update(array $columns): bool;
    public function delete(int $id): bool;
} /* end interface */
