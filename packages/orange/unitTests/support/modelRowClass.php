<?php

class modelRowClass
{
    public $id;
    public $first_name;
    public $last_name;
    public $age;

    public function __get($name)
    {
        $return = null;

        if (method_exists($this, $name)) {
            $return = $this->$name();
        } elseif (property_exists($this, $name)) {
            $return = $this->$name;
        }

        return $return;
    }

    public function full_name()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
