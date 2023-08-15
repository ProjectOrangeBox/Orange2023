<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class unitTestHelper extends TestCase
{
    protected $instance;
    /* support for private / protected properties and methods */

    protected function getPrivatePublic($attribute)
    {
        $getter = function () use ($attribute) {
            return $this->$attribute;
        };

        $closure = \Closure::bind($getter, $this->instance, get_class($this->instance));

        return $closure();
    }

    protected function setPrivatePublic($attribute, $value)
    {
        $setter = function ($value) use ($attribute) {
            $this->$attribute = $value;
        };

        $closure = \Closure::bind($setter, $this->instance, get_class($this->instance));

        $closure($value);
    }

    protected function callMethod(string $method, array $args = null)
    {
        $reflectionMethod = new ReflectionMethod($this->instance, $method);

        return (is_array($args)) ? $reflectionMethod->invokeArgs($this->instance, $args) : $reflectionMethod->invoke($this->instance);
    }

    protected function stripInvisible(string $string): string
    {
        return preg_replace('/[\x00-\x1F\x7F]/u', '', $string);
    }
}
