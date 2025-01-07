<?php

declare(strict_types=1);

namespace peels\acl\interfaces;

use peels\acl\interfaces\RoleModelInterface;
use peels\acl\interfaces\PermissionEntityInterface;

interface RoleEntityInterface
{
    public function update(): bool;
    public function deactive(): bool;
    public function active(): bool;
    public function addPermission(int|string|PermissionEntityInterface $arg): bool;
    public function removePermission(int|string|PermissionEntityInterface $arg): bool;
    public function removeAllPermissions(): bool;
}
