<?php

declare(strict_types=1);

namespace orange\framework\exceptions\http;

use Throwable;
use orange\framework\Error;

class Http301 extends Http
{
    protected string $url;

    public function __construct(string $url, string $message = '', int $code = 301, Throwable $previous = null)
    {
        $this->url = $url;

        parent::__construct($message, $code, $previous);
    }

    public function decorate(Error $error): void
    {
        $error->output->header('Location: ' . $this->url);

        parent::decorate($error);
    }
}
