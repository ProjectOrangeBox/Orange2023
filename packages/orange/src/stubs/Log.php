<?php

declare(strict_types=1);

namespace dmyers\orange\stubs;

use dmyers\orange\interfaces\LogInterface;

class Log implements LogInterface
{
    protected static LogInterface $instance;

    public function __construct(array $config)
    {
    }

    public static function getInstance(array $config): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config);
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

    public function __call($name, $arguments)
    {
    }
}
