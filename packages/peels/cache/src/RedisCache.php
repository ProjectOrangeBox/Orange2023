<?php

declare(strict_types=1);

namespace peels\cache;

use orange\framework\interfaces\CacheInterface;
use Framework\Cache\RedisCache as aplusRedisCache;

class RedisCache extends aplusRedisCache implements CacheInterface
{
    private static CacheInterface $instance;

    protected function __construct(array $config)
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
