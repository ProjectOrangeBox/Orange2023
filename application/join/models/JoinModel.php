<?php

declare(strict_types=1);

namespace application\join\models;

use peels\model\Model;

class JoinModel extends Model
{
    protected string $primaryColumn = 'id';
    protected int $primaryKey = 0;
    protected string $tablename = 'join_parent';

    protected array $rules = [
        'id' => ['isRequired|isInteger', 'Id'],
        'name' => ['isRequired|isString|isAlphaNumericSpace|maxLength[64]', 'Name'],
    ];
    protected array $ruleSets = [
        'create' => ['name'],
        'update' => ['id', 'name'],
        'delete' => ['id'],
        'read'   => ['id'],
    ];

    public function getByName(string $name): mixed
    {
        return $this->sql->select('*')->from($this->tablename)->whereEqual('name', $name)->execute()->row();
    }

    public function getAll(): array
    {
        return $this->crud->readAll();
    }

    public function getById(int $userid): array
    {
        // throws an ValidationFailed
        $this->validateFields('read', [$this->primaryColumn => $userid]);

        return $this->crud->read($userid);
    }

    public function create(array $fields): void
    {
        // throws an ValidationFailed
        $this->validateFields('create', $fields);

        $this->sql->insert()->into()->set($fields)->execute();
    }

    public function update(array $fields): void
    {
        // throws an ValidationFailed
        $this->validateFields('update', $fields);

        $this->sql->update()->set($fields)->wherePrimary($fields[$this->primaryColumn])->execute();
    }

    public function delete(array $fields): void
    {
        // throws an ValidationFailed
        $this->validateFields('delete', $fields);

        $this->sql->delete()->wherePrimary($fields[$this->primaryColumn])->execute();
    }
}
