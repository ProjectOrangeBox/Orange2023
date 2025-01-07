<?php

declare(strict_types=1);

namespace peels\acl\interfaces;

use peels\acl\interfaces\PermissionEntityInterface;

interface PermissionModelInterface
{
    public function create(array $columns): PermissionEntityInterface;
    public function update(array $columns): bool;
    public function delete(int $id): bool;
    public function read(string|int $key): PermissionEntityInterface;

    public function deactive(int $id): bool;
    public function active(int $id): bool;
} /* end interface */
