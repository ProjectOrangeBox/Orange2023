<?php

declare(strict_types=1);

use dmyers\orange\ModelAbstract;

class model extends ModelAbstract
{
    // required in extending class
    protected string $tablename = 'main';
    protected string $primaryColumn = 'id';

    public function getUser(int $id)
    {
        return $this->getById($id);
    }

    public function getUserDetailed(int $id)
    {
        $record = [];

        $dbc = $this->sql->select('main.id, first_name, last_name, join.id as jid, child_name')->where('main.id','=',$id)->innerJoin('join', 'main.id', 'join.parent_id')->run()->rows();

        if ($dbc) {
            foreach ($dbc as $record) {
                $child[] = [
                    'id' => $record['jid'],
                    'child_name' => $record['child_name'],
                ];
            }

            $record = [
                'fname' => $record['first_name'],
                'lname' => $record['last_name'],
                'childern' => $child,
            ];
        }

        return $record;
    }
}
