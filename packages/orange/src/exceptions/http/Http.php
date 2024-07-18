<?php

declare(strict_types=1);

namespace orange\framework\exceptions\http;

use Throwable;
use orange\framework\exceptions\OrangeException;

class Http extends OrangeException
{
    public function __construct(string $message = '', int $code = 0, Throwable $previous = null)
    {
        if ($code == 0) {
            $code = (int)substr(get_class($this), -3);

            if ($code == 0) {
                $code = 500;
            }
        }

        if (empty($message)) {
            $statusCodes = require __DIR__ . '/../../config/statusCodes.php';

            if (isset($statusCodes[$code])) {
                $message = $statusCodes[$code];
            } else {
                $message = 'Unknown Status Code ' . $code;
                $code = 500;
            }
        }

        parent::__construct($message, $code, $previous);
    }

    public function getHttpCode(): int
    {
        return $this->getCode();
    }
}
