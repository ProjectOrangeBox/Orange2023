<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\interfaces\DataInterface;
use orange\framework\base\SingletonArrayObject;

class Data extends SingletonArrayObject implements DataInterface
{
    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    protected function __construct(array $data = [])
    {
        logMsg('INFO', __METHOD__);

        parent::__construct($this->buildArrayObjects($data), \ArrayObject::ARRAY_AS_PROPS);
    }
}
