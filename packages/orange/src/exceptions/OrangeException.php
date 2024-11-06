<?php

declare(strict_types=1);

namespace orange\framework\exceptions;

use Throwable;
use orange\framework\Error;

// "parent" orange exception

class OrangeException extends \Exception
{
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        if (empty($message)) {
            /* since we must pass an array by ref into array_pop we need to put it into a variable */
            $className = explode('\\', get_class($this));

            $message = implode(' ', preg_split('/(?=[A-Z])/', array_pop($className)));
        }

        parent::__construct($message, $code, $previous);
    }

    public function decorate(Error $error): void
    {
        // child classes can extend this method and
        // access the properties & methods on the error class passed in
        // to interact with it.
    }
}
