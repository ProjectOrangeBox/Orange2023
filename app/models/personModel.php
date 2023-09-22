<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use app\models\personModelRow;
use dmyers\orange\ModelAbstract;

/**
 * Super Simple Model Example
 */
class personModel extends ModelAbstract {
    protected string $tablename = 'people';
    protected string $primaryColumn = 'id';
    protected string $fetchClass = personModelRow::class;
    protected int $defaultFetchType = PDO::FETCH_CLASS;

    public function getByName(string $name): mixed
    {
        return $this->sql->select('*')->from($this->tablename)->whereEqual('name',$name)->run()->row();
    }

    public function getAll(string $columns = '*', int $fetchMode = -1): array
    {
        return $this->sql->select('*')->from($this->tablename)->run()->rows();
    }

}