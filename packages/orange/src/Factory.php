<?php

declare(strict_types=1);

namespace orange\framework;

class Factory
{
    protected array $config = [];

    protected function __construct() {}

    protected function __clone() {}

    public function __wakeup() {}

    /**
     * Both return a new instance
     *
     * @return mixed
     */
    public static function getInstance(): mixed
    {
        $args = func_get_args();

        return (empty($args)) ? new static() : new static(...$args);
    }

    public static function newInstance(): mixed
    {
        $args = func_get_args();

        return (empty($args)) ? new static() : new static(...$args);
    }
}
