<?php

declare(strict_types=1);

namespace peels\asset\Interfaces;

interface PriorityInterface
{
    const FIRST = 5;
    const LOW = 10;
    const EARLIEST = 10;
    const EARLY = 25;
    const NORMAL = 50;
    const LATE = 75;
    const LATEST = 90;
    const LAST = 95;
    const HIGH = 90;

    public function has(string $name): bool;
    public function get(string $name): string;
    public function add(string $name, string $value, bool|int $append = true, int $priority = self::NORMAL): self;
    public function addMultiple(array $array, bool|int $append = true, int $priority = self::NORMAL): self;
}
