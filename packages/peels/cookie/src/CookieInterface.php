<?php

declare(strict_types=1);

namespace peels\cookie;

interface CookieInterface
{
    public function set(string $name, string $value = '', int $expire = -1, string $domain = '', string $path = '/', string $prefix = '', ?bool $secure = null, ?bool $httponly = null, ?bool $samesite = null): void;
    public function get(string $name, string $default = null): mixed;
    public function has(string $name): bool;
    public function remove(string $name, string $domain = '', string $path = '/'): void;
}
