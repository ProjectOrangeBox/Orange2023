<?php

declare(strict_types=1);

namespace peels\observer;

use SplSubject;
use SplObserver;

abstract class Client implements SplObserver
{
    private SplSubject $server;

    public function __construct(SplSubject $server)
    {
        $this->server = $server;
        $this->server->attach($this);
    }

    // must implement
    abstract public function update(SplSubject $caller): void;
}
