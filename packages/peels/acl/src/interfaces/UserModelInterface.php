<?php

declare(strict_types=1);

namespace peels\acl\interfaces;

use peels\acl\interfaces\UserEntityInterface;

interface UserModelInterface
{
    public function create(array $columns): UserEntityInterface;
    public function read(int $userId): UserEntityInterface;
    public function update(array $columns): bool;
    public function updatePassword(int $id, string $password): bool;
    public function delete(int $id): bool;

    public function deactive(int $id): bool;
    public function active(int $id): bool;

    public function relink(int $userId, array $roleIds): bool;
    public function addRole(int $userId, int $roleId): bool;
    public function removeRole(int $userId, int $roleId): bool;
    public function removeAllRoles(int $userId): bool;

    public function getRolesPermissions(int $userId): array;
    public function getMeta(int $userId): array;
} /* end interface */
