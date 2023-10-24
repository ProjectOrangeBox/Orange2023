<?php

declare(strict_types=1);

namespace dmyers\stash;

interface StashInterface
{
    public function push(): self;
    public function apply(): bool;
    public function __debugInfo(): array;
}
