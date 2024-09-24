<?php

declare(strict_types=1);

namespace orange\framework;

use ArrayObject;
use orange\framework\interfaces\DataInterface;
use orange\framework\exceptions\MagicMethodNotFound;
use orange\framework\exceptions\container\CannotUnserializeSingleton;

class Data extends ArrayObject implements DataInterface
{
    private static ?DataInterface $instance;

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    protected function __construct(array $data = [])
    {
        parent::__construct($this->buildArrayObjects($data), \ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Cloning and unserialization are not permitted for singletons.
     */
    protected function __clone()
    {
    }

    public function __wakeup()
    {
        throw new CannotUnserializeSingleton();
    }

    public static function getInstance(array $data = []): mixed
    {
        if (!isset(self::$instance)) {
            self::$instance = new static($data);
        }

        return self::$instance;
    }

    public function merge(array $array, bool $recursive = true, bool $replace = true): self
    {
        $currentArray = (array)$this;

        // more than likely you want to replace what is already in data not merge with it
        if ($replace) {
            $data = ($recursive) ? array_replace_recursive($currentArray, $array) : array_replace($currentArray, $array);
        } else {
            $data = ($recursive) ? array_merge_recursive($currentArray, $array) : array_merge($currentArray, $array);
        }

        // swap
        $this->exchangeArray($this->buildArrayObjects($data));

        return $this;
    }

    // let's "some" of the array_ functions work
    public function __call(string $name, array $arguments)
    {
        if (!is_callable($name) || substr($name, 0, 6) !== 'array_') {
            throw new MagicMethodNotFound(__CLASS__ . '->' . $name);
        }

        return call_user_func_array($name, array_merge([$this->getArrayCopy()], $arguments));
    }

    // build a recusrive array of ArrayObject's
    protected function buildArrayObjects(array $data)
    {
        $array = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = new self($value);
            }
            $array[$key] = $value;
        }

        return $array;
    }
}
