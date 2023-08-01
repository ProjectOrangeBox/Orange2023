<?php

declare(strict_types=1);

namespace peel\session;

use Framework\Session\Session as aplusSession;

class Session extends aplusSession implements SessionInterface
{
    private static SessionInterface $instance;

    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    public static function getInstance(array $config): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }
}
