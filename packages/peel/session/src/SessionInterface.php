<?php

declare(strict_types=1);

namespace peel\session;

interface SessionInterface
{
    public function __get(string $key): mixed;
    public function __set(string $key, mixed $value): void;
    public function __isset(string $key): bool;
    public function __unset(string $key): void;
    public function start(array $customOptions = []): bool;
    public function activate(): bool;
    public function isActive(): bool;
    public function destroy(): bool;
    public function destroyCookie(): bool;
    public function stop(): bool;
    public function abort(): bool;
    public function has(string $key): bool;
    public function get(string $key): mixed;
    public function getAll(): array;
    public function getMulti(array $keys): array;
    public function set(string $key, mixed $value): static;
    public function setMulti(array $items): static;
    public function remove(string $key): static;
    public function removeMulti(array $keys): static;
    public function removeAll(): static;
    public function regenerateId(bool $deleteOldSession = false): bool;
    public function reset(): bool;
    public function getFlash(string $key): mixed;
    public function setFlash(string $key, mixed $value): static;
    public function removeFlash(string $key): static;
    public function getTemp(string $key): mixed;
    public function setTemp(string $key, mixed $value, int $ttl = 60): static;
    public function removeTemp(string $key): static;
    public function id(string $newId = null): string | false;
    public function gc(): int | false;
}
