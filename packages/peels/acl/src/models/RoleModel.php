<?php

declare(strict_types=1);

namespace peels\acl\models;

use PDO;
use peels\model\Model;
use orange\framework\Application;
use peels\acl\entities\RoleEntity;
use peels\acl\interfaces\AclInterface;
use peels\acl\interfaces\RoleModelInterface;
use peels\acl\interfaces\RoleEntityInterface;
use orange\framework\traits\ConfigurationTrait;
use peels\validate\interfaces\ValidateInterface;
use peels\acl\exceptions\RecordNotFoundException;
use peels\acl\interfaces\PermissionEntityInterface;

class RoleModel extends Model implements RoleModelInterface
{
    use ConfigurationTrait;

    public AclInterface $acl;

    protected string $tableJoin;

    protected array $rules = [
        'id' => ['isRequired|integer', 'Id'],
        'name' => ['isRequired|minLength[4]|maxLength[128]|isUnique[%s,name,id,pdo]', 'Name'],
        'description' => ['isRequired|minLength[4]|maxLength[512]', 'Description'],
        'is_active' => ['ifEmpty[1]|isOneOf[0,1]', 'Is Active'],
    ];
    protected array $ruleSets = [
        'create' => ['name', 'description'],
        'update' => ['id', 'name', 'description'],
        'delete' => ['id'],
    ];

    public function __construct(array $config, PDO $pdo, ?ValidateInterface $validateService)
    {
        $this->mergeWith($config);

        $this->entityClass = $this->config['RoleEntityClass'] ?? \peels\acl\entities\RoleEntity::class;

        $this->config['tablename'] = $this->tablename = $this->config['role table'];

        $this->rules['name'][0] = sprintf($this->rules['name'][0], $this->tablename);

        $this->tableJoin = $this->config['role permission table'];

        $validateService->throwExceptionOnFailure(true);

        parent::__construct($this->config, $pdo, $validateService);

        $this->sql->throwExceptions(true);
    }

    public function create(array $columns): RoleEntityInterface
    {
        // throws an exception
        $this->validateFields('create', $columns);

        $pid = $this->sql->insert()->into($this->tablename)->values($columns)->execute()->lastInsertId();

        return $this->read($pid);
    }

    public function update(array $columns): bool
    {
        // throws an exception
        $this->validateFields('update', $columns);

        $this->sql->update($this->tablename)->set($columns)->where('id', '=', $columns['id'])->execute();

        return true;
    }

    public function delete(int $id): bool
    {
        // throws an exception
        $this->validateFields('delete', $columns);

        $this->sql->delete()->from($this->tablename)->where('id', '=', $id)->execute();
        $this->sql->delete()->from($this->tableJoin)->where('role_id', '=', $id)->execute();

        return true;
    }

    public function deactive(int $id): bool
    {
        $this->sql->update($this->tablename)->set(['is_active' => 0])->where('id', '=', $id)->execute();

        return true;
    }

    public function active(int $id): bool
    {
        $this->sql->update($this->tablename)->set(['is_active' => 1])->where('id', '=', $id)->execute();

        return true;
    }

    public function read(string|int $key): RoleEntityInterface
    {
        $column = (is_string($key)) ? 'name' : 'id';

        if ($this->sql->setFetchMode($this->entityClass, [$this->config, $this])->select()->from($this->tablename)->where($column, '=', $key)->execute()->rowCount() > 0) {
            $roleEntity = $this->sql->row();
        } else {
            throw new RecordNotFoundException('Role Record ' . $key);
        }

        return $roleEntity;
    }

    public function readAll(): array
    {
        return $this->sql->select()->from($this->tablename)->execute()->rows();
    }

    public function addPermission(int $roleId, int|string|PermissionEntityInterface $arg): bool
    {
        if (is_string($arg)) {
            $permissionEntity = $this->acl->getRole($arg);
            $permissionId = (int)$permissionEntity->id;
        } elseif (is_object($arg)) {
            $permissionId = $arg->id;
        } else {
            $permissionId = $arg;
        }

        $this->sql->insert()->into($this->tableJoin)->values(['role_id' => $roleId, 'permission_id' => $permissionId])->execute();

        return true;
    }

    public function removePermission(int $roleId, int|string|PermissionEntityInterface $arg): bool
    {
        if (is_string($arg)) {
            $permissionEntity = $this->acl->getRole($arg);
            $permissionId = (int)$permissionEntity->id;
        } elseif (is_object($arg)) {
            $permissionId = $arg->id;
        } else {
            $permissionId = $arg;
        }

        $this->sql->delete($this->tableJoin)->where('role_id', '=', $roleId)->and()->where('permission_id', '=', $permissionId)->execute();

        return true;
    }

    public function removeAllPermissions(int $userId): bool
    {
        $this->sql->delete()->from($this->tableJoin)->where('user_id', '=', $userId)->execute();

        return true;
    }

    public function relink(int $roleId, array $permissionIds): bool
    {
        $this->sql->pdo->beginTransaction();

        $this->removeAllPermissions($roleId);

        foreach ($permissionIds as $permissionId) {
            $this->addPermission($roleId, $permissionId);
        }

        if ($this->sql->hasError()) {
            $this->sql->pdo->rollBack();
        } else {
            $this->sql->pdo->commit();
        }

        return $this->sql->hasError();
    }
}
