<?php

declare(strict_types=1);

namespace peels\acl\entities;

use peels\acl\interfaces\PermissionEntityInterface;
use peels\acl\interfaces\PermissionModelInterface;

class PermissionEntity implements PermissionEntityInterface
{
    protected PermissionModelInterface $permissionModel;
    protected array $config = [];

    public readonly int $id;
    public string $key;
    public string $description;
    public string $group;
    public readonly ?string $migration;
    public readonly string $is_active;

    public function __construct(array $config, PermissionModelInterface $permissionModel)
    {
        $this->config = $config;
        $this->permissionModel = $permissionModel;
    }

    public function update(): bool
    {
        $columns = get_object_vars(...)->__invoke($this);

        return $this->permissionModel->update($columns);
    }

    public function deactive(): bool
    {
        return $this->permissionModel->deactive($this->id);
    }

    public function active(): bool
    {
        return $this->permissionModel->active($this->id);
    }
}
