<?php

declare(strict_types=1);

namespace peel\cache;

use Framework\Cache\Cache;

class FilesCache extends Cache implements CacheInterface
{
    private static CacheInterface $instance;

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

    public function get(string $key): mixed
    {
        return null;
    }

    public function set(string $key, mixed $value, int $ttl = null): bool
    {
        return true;
    }
    
    public function delete(string $key): bool
    {
        return true;
    }
    
    public function flush(): bool
    {
        return true;
    }
}
