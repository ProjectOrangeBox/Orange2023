<?php

declare(strict_types=1);

namespace orange\framework;

use ArrayObject;
use orange\framework\interfaces\DataInterface;

class Data extends ArrayObject implements DataInterface
{
    private static ?DataInterface $instance = null;

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    protected function __construct(array $data = [])
    {
        $this->merge($data);
    }

    public static function getInstance(array $data = []): self
    {
        if (self::$instance === null) {
            self::$instance = new self($data);
        }

        return self::$instance;
    }

    public function merge(array $array, bool $recursive = true, bool $replace = true): self
    {
        $current = (array)$this;

        // more than likely you want to replace what is already in data not merge with it
        if ($replace) {
            $newArray = ($recursive) ? array_replace_recursive($current, $array) : array_replace($current, $array);
        } else {
            $newArray = ($recursive) ? array_merge_recursive($current, $array) : array_merge($current, $array);
        }

        // swap
        $this->exchangeArray($newArray);

        return $this;
    }
}
