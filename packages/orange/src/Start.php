<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\Application;

class Start
{
    public static function __callStatic($name, $arguments): Application
    {
        return new Application($arguments[0], $name);
    }
}
