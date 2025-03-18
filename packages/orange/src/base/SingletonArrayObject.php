<?php

declare(strict_types=1);

namespace orange\framework\base;

use ArrayObject;
use orange\framework\exceptions\MagicMethodNotFound;
use orange\framework\exceptions\container\CannotUnserializeSingleton;

class SingletonArrayObject extends ArrayObject
{
    // the default instance config
    protected array $config = [];

    /**
     * The actual singleton's instance almost always resides inside a static
     * field. In this case, the static field is an array, where each subclass of
     * the Singleton stores its own instance.
     */
    private static array $instances = [];

    /**
     * Singleton's constructor should not be public. However, it can't be
     * private either if we want to allow subclassing.
     */
    protected function __construct()
    {
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

    /**
     * The method you use to get the Singleton's instance.
     */
    public static function getInstance(): mixed
    {
        $subclass = static::class;

        if (!isset(self::$instances[$subclass])) {
            // Note that here we use the "static" keyword instead of the actual
            // class name. In this context, the "static" keyword means "the name
            // of the current class". That detail is important because when the
            // method is called on the subclass, we want an instance of that
            // subclass to be created here.
            $args = func_get_args();

            self::$instances[$subclass] = self::newInstance(...$args);
        }

        return self::$instances[$subclass];
    }

    /**
     * For unit testing
     */
    public static function destroyInstance(): void
    {
        unset(self::$instances[static::class]);
    }

    /**
     * Allow the creation of a new instance for testing etc...
     */
    public static function newInstance(): mixed
    {
        $args = func_get_args();

        return (empty($args)) ? new static() : new static(...$args);
    }

    /**
     * Allow ArrayObject "merging"
     *
     * @param array $array
     * @param bool $recursive
     * @param bool $replace
     * @return static
     */
    public function merge(array $array, bool $recursive = true, bool $replace = true): static
    {
        // convert ArrayObject into an array
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
    /**
     * let "some" of the array_ functions work
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws MagicMethodNotFound
     */
    public function __call(string $name, array $arguments)
    {
        if (!is_callable($name) || substr($name, 0, 6) !== 'array_') {
            throw new MagicMethodNotFound(__CLASS__ . '->' . $name);
        }

        return call_user_func_array($name, array_merge([$this->getArrayCopy()], $arguments));
    }

    /**
     * build a recusrive array of ArrayObject's
     *
     * @param array $data
     * @return array
     */
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
