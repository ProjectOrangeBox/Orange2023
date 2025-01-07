<?php

declare(strict_types=1);

namespace orange\framework\base;

use orange\framework\exceptions\container\CannotUnserializeSingleton;

/**
 * Extend and replace some of Factories methods
 */
class Singleton extends Factory
{
    /**
     * The actual singleton's instance almost always resides inside a static
     * field. In this case, the static field is an array, where each subclass of
     * the Singleton stores its own instance.
     */
    private static array $instances = [];

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
        $subclass = static::class;

        unset(self::$instances[$subclass]);
    }
}
