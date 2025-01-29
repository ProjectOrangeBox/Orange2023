<?php

declare(strict_types=1);

namespace peels\cookie;

interface CookieInterface
{
    public function set(string $name, string $value = '', int $expires = 0, string $path = '', string $domain = '', bool $secure = false, bool $httpOnly = false, string $sameSite = ''): void;
    public function get(string $name, string $default = null): mixed;
    public function has(string $name): bool;
    public function remove(string $name, string $domain = '', string $path = '/'): void;
}
