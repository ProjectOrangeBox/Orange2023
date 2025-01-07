<?php

declare(strict_types=1);

namespace peels\stash;

interface StashInterface
{
    public function push(): self;
    public function apply(): bool;
}
