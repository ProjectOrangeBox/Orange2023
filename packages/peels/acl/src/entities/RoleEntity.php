<?php

declare(strict_types=1);

namespace peels\acl\entities;

use peels\acl\interfaces\PermissionEntityInterface;
use peels\acl\interfaces\RoleEntityInterface;
use peels\acl\interfaces\RoleModelInterface;

class RoleEntity implements RoleEntityInterface
{
    protected RoleModelInterface $roleModel;
    protected array $config = [];

    public readonly int $id;
    // short name of role
    public string $name;
    // description of role
    public string $description;
    // migration which added the role
    public readonly ?string $migration;
    // if the permission is active or not
    public readonly string $is_active;

    public function __construct(array $config, RoleModelInterface $roleModel)
    {
        $this->config = $config;
        $this->roleModel = $roleModel;
    }

    public function update(): bool
    {
        // get the public columns from the entity
        $columns = get_object_vars(...)->__invoke($this);

        return $this->roleModel->update($columns);
    }

    public function deactive(): bool
    {
        return $this->roleModel->deactive($this->id);
    }

    public function active(): bool
    {
        return $this->roleModel->active($this->id);
    }

    public function addPermission(int|string|PermissionEntityInterface $arg): bool
    {
        return $this->roleModel->addPermission($this->id, $arg);
    }

    public function removePermission(int|string|PermissionEntityInterface $arg): bool
    {
        return $this->roleModel->removePermission($this->id, $arg);
    }

    public function removeAllPermissions(): bool
    {
        return $this->roleModel->removeAllPermissions($this->id);
    }
}
