<?php

declare(strict_types=1);

namespace people\models;

use dmyers\orange\ModelAbstract;

class parentModel extends ModelAbstract {
    protected string $tablename = 'parent';
    protected string $primaryColumn = 'id';

    public function getByName(string $name): mixed
    {
        return $this->sql->select('*')->from($this->tablename)->whereEqual('name',$name)->run()->row();
    }
}