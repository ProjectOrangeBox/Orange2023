<?php

declare(strict_types=1);

namespace application\people\models;

use peels\model\Model;

class PeopleModel extends Model
{
    protected string $primaryColumn = 'id';
    protected int $primaryKey = 0;

    protected array $rules = [
        'id' => ['isRequired|isInteger', 'Id'],
        'firstname' => ['isRequired|isString|isAlphaNumericSpace|maxLength[32]', 'First Name'],
        'lastname' => ['isRequired|isString|isAlphaNumericSpace|maxLength[32]', 'Last Name'],
        'age' => ['isRequired|isInteger|isGreaterThan[17]|isLessThan[111]', 'Age'],
    ];
    protected array $ruleSets = [
        'create' => ['firstname', 'lastname', 'age'],
        'update' => ['id', 'firstname', 'lastname', 'age'],
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
