<?php

declare(strict_types=1);

namespace peels\acl\interfaces;

use peels\acl\interfaces\PermissionModelInterface;

interface PermissionEntityInterface
{
    public function update(): bool;
    public function deactive(): bool;
    public function active(): bool;
}
