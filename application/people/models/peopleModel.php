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
        'color' => ['isRequired|isInteger|isGreaterThan[0]|isLessThan[16]', 'Color'],
    ];
    protected array $ruleSets = [
        'create' => ['firstname', 'lastname', 'age', 'color'],
        'update' => ['id', 'firstname', 'lastname', 'age', 'color'],
        'delete' => ['id'],
        'read'   => ['id'],
    ];

    public function getByName(string $name): mixed
    {
        return $this->sql->select('*')->from($this->tablename)->whereEqual('name', $name)->execute()->row();
    }

    public function getNew(): array
    {
        return ['color' => 3, 'firstname' => '', 'lastname' => '', 'age' => 18];
    }

    public function getAll(): array
    {
        return $this->sql->select(['people.id', 'people.firstname', 'people.lastname', 'people.age', 'people.color as color', 'color.name as colorname'])->from($this->tablename)->leftJoin('color', 'people.color', 'color.id')->execute()->rows();
    }

    public function getById(int $userid): array
    {
        // throws an ValidationFailed
        $fields = $this->validateFields('read', [$this->primaryColumn => $userid]);

        return $this->sql->select(['people.id', 'people.firstname', 'people.lastname', 'people.age', 'people.color as color', 'color.name as colorname'])->from($this->tablename)->leftJoin('color', 'people.color', 'color.id')->wherePrimary($fields[$this->primaryColumn])->execute()->row();
    }

    public function create(array $fields): string|false
    {
        // throws an ValidationFailed
        $fields = $this->validateFields('create', $fields);

        $this->sql->insert()->into()->set($fields)->execute();

        return $this->getLastInsertId();
    }

    public function update(array $fields): void
    {
        // throws an ValidationFailed
        $fields = $this->validateFields('update', $fields);

        $this->sql->update()->set($fields)->wherePrimary($fields[$this->primaryColumn])->execute();
    }

    public function delete(array $fields): void
    {
        // throws an ValidationFailed
        $fields = $this->validateFields('delete', $fields);

        $this->sql->delete()->wherePrimary($fields[$this->primaryColumn])->execute();
    }
}
