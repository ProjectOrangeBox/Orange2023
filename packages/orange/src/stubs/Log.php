<?php

declare(strict_types=1);

namespace orange\framework\stubs;

use orange\framework\interfaces\LogInterface;

class Log implements LogInterface
{
    protected static ?LogInterface $instance = null;

    protected function __construct()
    {
        // do nothing
    }

    public static function getInstance(array $config): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function changeThreshold(int $threshold): self
    {
        return $this;
    }

    public function getThreshold(): int
    {
        return 0;
    }

    public function isEnabled(): bool
    {
        return false;
    }

    protected function convertLogLevelToString(int $level): string
    {
        return 'NONE';
    }

    protected function convertLogLevelToInt(string $level): int
    {
        return 0;
    }

    public function write(int $level, string $message): void
    {
        // do nothing
    }

    public function __call($name, $arguments)
    {
        // do nothing
    }

    public function __debugInfo(): array
    {
        return [];
    }
}
