<?php

declare(strict_types=1);

namespace peels\session;

use Framework\Session\SaveHandler;
use Framework\Session\Session as aplusSession;

class Session extends aplusSession implements SessionInterface
{
    private static SessionInterface $instance;

    public function __construct(array $options = [], SaveHandler $handler = null)
    {
        parent::__construct($options, $handler);
    }

    public static function getInstance(array $options = [], SaveHandler $handler = null): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($options, $handler);
        }

        return self::$instance;
    }
}
