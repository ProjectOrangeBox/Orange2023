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
        /* must pass by ref into array_pop */
        $className = explode('\\', get_class($this));

        $humanException = implode(' ', preg_split('/(?=[A-Z])/', array_pop($className)));

        parent::__construct($humanException . ' - ' . $message, $code, $previous);
    }

    public function decorate(Error $error): void
    {
        // place holder
    }
}
