<?php

declare(strict_types=1);

namespace peels\acl\interfaces;

use peels\acl\interfaces\RoleEntityInterface;

interface RoleModelInterface
{
    public function create(array $columns): RoleEntityInterface;
    public function update(array $columns): bool;
    public function delete(int $id): bool;
    public function read(string|int $key): RoleEntityInterface;

    public function deactive(int $id): bool;
    public function active(int $id): bool;

    public function relink(int $roleId, array $permissionIds): bool;

    public function addPermission(int $roleId, int $permissionId): bool;
    public function removePermission(int $roleId, int $permissionId): bool;
    public function removeAllPermissions(int $userId): bool;
} /* end interface */
