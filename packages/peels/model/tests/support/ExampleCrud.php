<?php

declare(strict_types=1);

use peels\model\Model;

class ExampleCrud extends Model
{
    // required in extending class
    protected string $tablename = 'main';
    protected string $primaryColumn = 'id';

    public function read(int $id): array
    {
        return $this->crud->read($id);
    }

    public function readAll(): array
    {
        return $this->crud->readAll();
    }

    public function create(string $lastname, string $firstname, int $age): int
    {
        return $this->crud->create(['first_name' => $firstname, 'last_name' => $lastname, 'age' => $age]);
    }

    public function update(array $columnValues, int $id): bool
    {
        return $this->crud->update($columnValues, $id);
    }

    public function delete(int $id): bool
    {
        return $this->crud->delete($id);
    }
}
