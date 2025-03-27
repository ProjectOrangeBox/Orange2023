<?php

declare(strict_types=1);

use orange\framework\interfaces\InputInterface;
use orange\framework\interfaces\OutputInterface;

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
    function config(?string $filename = null, ?string $key = null, mixed $default = null): mixed
    {
        try {
            if ($filename === null && $key === null && $default === null) {
                $config = container()->config;
            } else {
                $config = container()->config->get($filename, $key, $default);
            }
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
    function getUrl(string $searchName, array $arguments = [], ?bool $skipCheckingType = null): string
    {
        // throws an exception if the router service isn't setup
        return container()->router->getUrl($searchName, $arguments, $skipCheckingType);
    }
}

/* wrapper for input */
if (!function_exists('input')) {
    function input(): InputInterface
    {
        return container()->input;
    }
}

/* wrapper for output */
if (!function_exists('output')) {
    function output(): OutputInterface
    {
        return container()->output;
    }
}
