<?php

declare(strict_types=1);

/**
 * container services wrapper functions which are usually a single line
 * or some safety checks and a single line
 */

use orange\framework\interfaces\ContainerInterface;

// if you provide your own container override this
if (!function_exists('container')) {
    function container(): ContainerInterface
    {
        return orange\framework\Container::getInstance();
    }
}

/**
 * Easy Access to logging
 * works only if logging service exists
 *
 * override as needed
 */
if (!function_exists('logMsg')) {
    function logMsg(mixed $level, string $msg): void
    {
        // don't throw an error if it's not available
        if (function_exists('container')) {
            if (container()->has('log')) {
                container()->log->write(container()->log->convert2($level, true), $msg);
            }
        }
    }
}

/**
 * wrapper to read a config value
 */
if (!function_exists('config')) {
    function config(string $filename, string $key, mixed $default = null): mixed
    {
        // throws error if service missing
        return container()->config->get($filename, $key, $default);
    }
}

/* wrapper for router get url */
if (!function_exists('getUrl')) {
    function getUrl(string $searchName, array $arguments = []): string
    {
        return container()->router->getUrl($searchName, $arguments);
    }
}
