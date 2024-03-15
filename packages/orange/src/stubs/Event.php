<?php

declare(strict_types=1);

namespace orange\framework\stubs;

use orange\framework\interfaces\EventInterface;

class Event implements EventInterface
{
    protected static ?EventInterface $instance = null;

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

    public function register($name, $callable, int $priority = self::PRIORITY_NORMAL): int
    {
        return 0;
    }

    public function registerMultiple(array $multiple, int $priority = self::PRIORITY_NORMAL): array
    {
        return [];
    }

    protected function registerClosureEvent(string $name, $callable, int $priority): void
    {
        // do nothing
    }

    public function trigger(string $name, &...$arguments): self
    {
        return $this;
    }


    public function triggers(): array
    {
        return [];
    }

    public function has(string $name): bool
    {
        return true;
    }

    public function events(): array
    {
        return [];
    }

    public function count(string $name): int
    {
        return 0;
    }

    public function unregister(int $eventId): bool
    {
        return true;
    }

    public function unregisterAll(string $name = null): bool
    {
        return true;
    }

    public function __debugInfo(): array
    {
        return [];
    }
}
