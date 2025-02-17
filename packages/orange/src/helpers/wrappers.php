<?php

declare(strict_types=1);

/*
 * This is the easiest way to get the container instance
 * which is attached to the Application Class
 */
if (!function_exists('container')) {
    function container(): orange\framework\interfaces\ContainerInterface
    {
        // wrapper for...
        return \orange\framework\Container::getInstance();
    }
}

/*
 * Easy Access to logging
 * works only if logging service exists
 *
 * override as needed
 */
if (!function_exists('logMsg')) {
    function logMsg(mixed $level, string $msg, array $context = []): void
    {
        try {
            container()->log->log($level, $msg, $context);
        } catch (Throwable $e) {
            // good chance the container or log isn't setup yet
            // so we can't do anything yet
        }
    }
}

/* wrapper to read a config value */
if (!function_exists('config')) {
    function config(string $filename, string $key, mixed $default = null): mixed
    {
        try {
            $config = container()->config->get($filename, $key, $default);
        } catch (Throwable $e) {
            // config not setup?
            // fallback to default
            $config = $default;
        }

        return $config;
    }
}

/* wrapper for router get url */
if (!function_exists('getUrl')) {
    function getUrl(): string
    {
        $arguments = func_get_args();

        $searchName = array_shift($arguments);

        // throws an exception if the router service isn't setup
        return container()->router->getUrl($searchName, $arguments);
    }
}

/* wrapper for router get url with no type checking */
if (!function_exists('getUrlSkip')) {
    function getUrlSkip(): string
    {
        $arguments = func_get_args();

        $searchName = array_shift($arguments);

        // throws an exception if the router service isn't setup
        return container()->router->getUrlNoCheck($searchName, $arguments);
    }
}