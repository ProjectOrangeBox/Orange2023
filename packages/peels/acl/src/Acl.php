<?php

declare(strict_types=1);

namespace peels\acl;

use PDO;
use peels\acl\models\RoleModel;
use peels\acl\models\UserModel;
use orange\framework\Application;
use orange\framework\base\Singleton;
use peels\acl\models\PermissionModel;
use peels\acl\interfaces\AclInterface;
use peels\acl\interfaces\RoleEntityInterface;
use peels\acl\interfaces\UserEntityInterface;
use orange\framework\traits\ConfigurationTrait;
use peels\validate\interfaces\ValidateInterface;
use peels\acl\interfaces\PermissionEntityInterface;

class Acl extends Singleton implements AclInterface
{
    use ConfigurationTrait;

    // we manage these
    public UserModel $userModel;
    public RoleModel $roleModel;
    public PermissionModel $permissionModel;

    protected function __construct(array $config, PDO $pdo, ValidateInterface $validateService)
    {
        $this->mergeWithDefault($config);

        $this->userModel = new $config['userModel']($config, $pdo, $validateService);
        $this->roleModel = new $config['roleModel']($config, $pdo, $validateService);
        $this->permissionModel = new $config['permissionModel']($config, $pdo, $validateService);
    }

    /**
     * get & create entities
     *
     * create will throw ValidationFailed Exceptions on fail
     */
    public function createUser(string $username, string $email, string $password, array $fields = []): UserEntityInterface
    {
        $fields = $fields + [
            'username' => $username,
            'email' => $email,
            'password' => $password,
        ];

        return $this->userModel->create($fields);
    }

    public function getUser(int $userId): UserEntityInterface
    {
        return $this->userModel->read($userId);
    }

    public function createRole(string $name, string $description): RoleEntityInterface
    {
        return $this->roleModel->create(['name' => $name, 'description' => $description]);
    }

    public function getRole(string|int $arg): RoleEntityInterface
    {
        return $this->roleModel->read($arg);
    }

    public function createPermission(string $key, string $description, string $group): PermissionEntityInterface
    {
        return $this->permissionModel->create(['key' => $key, 'description' => $description, 'group' => $group]);
    }

    public function getPermission(string|int $arg): PermissionEntityInterface
    {
        return $this->permissionModel->read($arg);
    }
}
