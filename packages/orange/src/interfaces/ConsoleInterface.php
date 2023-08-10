<?php

declare(strict_types=1);

namespace dmyers\orange\interfaces;

interface ConsoleInterface
{
    public function echo(string $string, bool $linefeed = true): void;
    public function error(string $string, bool $linefeed = true): void;
    public function stop(string $string, bool $linefeed = true): void;
    public function bell(int $count = 1): void;
    public function line(int $length = null, string $char = '─'): void;
    public function __debugInfo(): array;
}
