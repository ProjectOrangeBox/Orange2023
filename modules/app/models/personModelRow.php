<?php

declare(strict_types=1);

namespace app\models;

class personModelRow
{
    public int $id;
    public string $name;
    public int $age;
    public string $phone;

    public function __get($name)
    {
        $return = '';
        
        switch ($name) {
            case 'combo':
                $return = 'User Id: '.$this->id . ' ' . $this->name . ' has the phone number of ' . $this->phone;
                break;
        }

        return $return;
    }
}
