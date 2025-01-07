<?php

declare(strict_types=1);

namespace peels\acl\interfaces;

use peels\acl\interfaces\UserModelInterface;
use peels\acl\interfaces\RoleEntityInterface;

interface UserEntityInterface
{
    public function lazyLoad(): void;
    public function update(): bool;
    public function deactive(): bool;
    public function active(): bool;
    public function addRole(string|int|RoleEntityInterface $arg): bool;
    public function removeRole(string|int|RoleEntityInterface $arg): bool;
    public function removeAllRoles(): bool;

    /* access */
    public function can(string $permission): bool;
    public function hasRole(int $role): bool;
    public function hasRoles(array $roles): bool;
    public function hasOneRoleOf(array $roles): bool;
    public function hasPermissions(array $permissions): bool;
    public function hasOnePermissionOf(array $permissions): bool;
    public function hasPermission(string $permission): bool;
    public function cannot(string $permission): bool;

    public function __set(string $name, mixed $value): void;
    public function __get(string $name): mixed;
    public function loggedIn(): bool;
    public function isAdmin(): bool;
    public function isGuest(): bool;
}
