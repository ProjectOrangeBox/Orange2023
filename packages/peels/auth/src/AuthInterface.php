<?php

declare(strict_types=1);

namespace peels\auth;

interface AuthInterface
{
    public function userId(): int;

    public function error(): string;
    public function hasError(): bool;

    public function login(string $login, string $password): bool;
    public function logout(): bool;
}
