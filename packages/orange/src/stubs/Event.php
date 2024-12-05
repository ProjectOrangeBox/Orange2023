<?php

declare(strict_types=1);

namespace orange\framework\stubs;

use orange\framework\Event as FrameworkEvent;
use orange\framework\interfaces\EventInterface;

class Event extends FrameworkEvent implements EventInterface
{
    public function trigger(string $name, &...$arguments): self
    {
        // do nothing
        return $this;
    }
}
