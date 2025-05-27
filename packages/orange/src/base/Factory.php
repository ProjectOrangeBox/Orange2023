<?php

declare(strict_types=1);

namespace orange\framework\base;

/**
 * Parent Class
 */
class Factory
{
    protected array $config = [];

    /**
     * constructor should not be public. However, it can't be
     * private either if we want to allow subclassing.
     */
    protected function __construct()
    {
    }

    /**
     * Cloning and unserialization are not permitted by default
     */
    protected function __clone()
    {
    }

    /**
     * Wakeup is not permitted by default
     */
    public function __wakeup()
    {
    }

    /**
     * Both return a new instance
     *
     * @return mixed
     */
    public static function getInstance(): mixed
    {
        $args = func_get_args();

        return self::newInstance(...$args);
    }

    public static function newInstance(): mixed
    {
        $args = func_get_args();

        return (empty($args)) ? new static() : new static(...$args);
    }
}
