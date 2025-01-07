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

// you can override these 2 functions with your prefered "fast" PHP array caching functions
// For testing simply return
// a true for FastPHPArrayCacheWrite()
// false for FastPHPArrayCacheRead()
if (!function_exists('FastPHPArrayCacheWrite')) {
    function FastPHPArrayCacheWrite(string $filename, array $array): bool
    {
        $filePath = __ROOT__ . '/var/cache/' . $filename . '.php';

        return file_put_contents_atomic($filePath, '<?php' . PHP_EOL . 'return ' . var_export($array, true) . ';' . PHP_EOL) > 0;
    }
}

if (!function_exists('FastPHPArrayCacheRead')) {
    function FastPHPArrayCacheRead(string $filename): false|array
    {
        $filePath = __ROOT__ . '/var/cache/' . $filename . '.php';

        $cached = null;

        if (file_exists($filePath)) {
            $cached = include $filePath;
        }

        return is_array($cached) ? $cached : false;
    }
}
