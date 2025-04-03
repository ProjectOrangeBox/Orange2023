<?php

declare(strict_types=1);

namespace application\people\models;

use peels\model\Model;

class ColorModel extends Model
{
    protected string $primaryColumn = 'id';
    protected int $primaryKey = 0;

    protected array $rules = [
        'id' => ['isRequired|isInteger', 'Id'],
        'name' => ['isRequired|isString|maxLength[32]', 'Name'],
    ];
    protected array $ruleSets = [
        'create' => ['name'],
        'update' => ['id', 'name'],
        'delete' => ['id'],
        'read'   => ['id'],
    ];

    public function getAll(): array
    {
        return $this->crud->readAll();
    }
}
