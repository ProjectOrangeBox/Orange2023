<?php

declare(strict_types=1);

namespace peels\acl\models;

use PDO;
use peels\model\Model;
use peels\validate\interfaces\ValidateInterface;
use peels\acl\exceptions\RecordNotFoundException;
use peels\acl\interfaces\PermissionModelInterface;
use peels\acl\interfaces\PermissionEntityInterface;

class PermissionModel extends Model implements PermissionModelInterface
{
    protected array $rules = [
        'id' => ['isRequired|integer', 'Id'],
        'key' => ['isRequired|minLength[4]|maxLength[255]|isUnique[%s,key,id,pdo]', 'Key'],
        'description' => ['isRequired|minLength[4]|maxLength[512]', 'Description'],
        'group' => ['isRequired|minLength[4]|maxLength[128]', 'Group'],
        'is_active' => ['ifEmpty[1]|isOneOf[0,1]', 'Is Active'],
    ];
    protected array $ruleSets = [
        'create' => ['key', 'description', 'group', 'is_active'],
        'update' => ['id', 'key', 'description', 'group', 'is_active'],
        'delete' => ['id'],
    ];

    public function __construct(array $config, PDO $pdo, ?ValidateInterface $validateService)
    {
        $this->entityClass = $config['PermissionEntityClass'] ?? \peels\acl\entities\PermissionEntity::class;

        $config['tablename'] = $this->tablename = $config['permission table'];

        $this->rules['key'][0] = sprintf($this->rules['key'][0], $this->tablename);

        $validateService->throwExceptionOnFailure(true);

        parent::__construct($config, $pdo, $validateService);

        $this->sql->throwExceptions(true);
    }

    public function create(array $columns): PermissionEntityInterface
    {
        // throws exception
        $this->validateFields('create', $columns);

        $pid = $this->sql->insert()->into($this->tablename)->values($columns)->execute()->lastInsertId();

        return $this->read($pid);
    }

    public function update(array $columns): bool
    {
        // throws exception
        $this->validateFields('update', $columns);

        $this->sql->update($this->tablename)->set($columns)->where('id', '=', $columns['id'])->execute();

        return true;
    }

    public function delete(int $id): bool
    {
        // throws exception
        $this->validateFields('delete', ['id' => $id]);

        $this->sql->delete()->from($this->tablename)->where('id', '=', $id)->execute();
        $this->sql->delete()->from('orange_role_permission')->where('permission_id', '=', $id)->execute();

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

    public function read(string|int $key): PermissionEntityInterface
    {
        $column = (is_string($key)) ? 'name' : 'id';

        if ($this->sql->setFetchMode($this->entityClass, [$this->config, $this])->select()->from($this->tablename)->where($column, '=', $key)->execute()->rowCount() > 0) {
            $permissionEntity = $this->sql->row();
        } else {
            throw new RecordNotFoundException('Permission Record ' . $key);
        }

        return $permissionEntity;
    }

    public function readAll(): array
    {
        return $this->sql->select()->from($this->tablename)->execute()->rows();
    }
}
