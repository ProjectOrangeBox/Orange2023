<?php

declare(strict_types=1);

namespace peel\cache;

use Framework\Cache\MemcachedCache as aplusMemcachedCache;

class MemcachedCache extends aplusMemcachedCache implements CacheInterface
{
    private static CacheInterface $instance;

    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    public static function getInstance(array $config): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }
}
