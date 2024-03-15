<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\exceptions\CannotUnserializeSingleton;

abstract class Singleton
{
    /**
     * prevent the instance from being cloned (which would create a second instance of it)
     */
    private function __clone()
    {
    }

    /**
     * prevent from being unserialized (which would create a second instance of it)
     */
    public function __wakeup()
    {
        throw new CannotUnserializeSingleton();
    }
}