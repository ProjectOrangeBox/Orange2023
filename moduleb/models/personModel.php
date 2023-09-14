<?php

declare(strict_types=1);

namespace example\moduleb\models;

use dmyers\orange\ModelAbstract;

class personModel extends ModelAbstract {
    protected string $tablename = 'people';
    protected string $primaryColumn = 'id';

    public function getByName(string $name): mixed
    {
        return $this->sql->select('*')->from($this->tablename)->whereEqual('name',$name)->run()->row();
    }
}