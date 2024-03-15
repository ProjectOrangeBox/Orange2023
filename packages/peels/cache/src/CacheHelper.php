<?php

declare(strict_types=1);

/**
 * Wrapper for getting something from the cache with a few options
 */

if (!function_exists('cacheGetOr')) {
    function cacheGetOr(string $cacheKey, object $class, string $method, array $arguments = [], int $expires = -1)
    {
        // This will throw an error if a "cache" service isn't setup
        $cacheService = container()->cache;

        $cached = $cacheService->get($cacheKey);

        // Did we get null
        if ($cached === null) {
            // Call the class with the method provided and the clean arguments as a 0-index array
            $cached = call_user_func_array([$class, $method], array_values($arguments));

            if (defined('TTL') && $expires < 0) {
                $expires = TTL;
            }

            // cache it
            $cacheService->set($cacheKey, $cached, $expires);
        }

        return $cached;
    }
}

if (!function_exists('cacheGet')) {
    function cacheGet(string $key)
    {
        return container()->cache->get($key);
    }
}

if (!function_exists('cacheSet')) {
    function cacheSet(string $key, mixed $value, int $ttl = null)
    {
        return container()->cache->set($key, $value, $ttl);
    }
}

if (!function_exists('cacheDelete')) {
    function cacheDelete(string $cacheKey): void
    {
        container()->cache->delete($cacheKey);
    }
}
