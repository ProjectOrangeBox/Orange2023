<?php

declare(strict_types=1);

/*
 * This is the MAIN container generator function
 * You can only supply the config array once
 * this is provided in the Application::bootstrap(...) method
 * after that the container is setup and any additional calls will return the same instance each time
 * regardless of what is sent in
 *
 * if you provide your own container override this
 */

if (!function_exists('container')) {
    function container(): orange\framework\interfaces\ContainerInterface
    {
        // wrapper for...
        return orange\framework\Container::getInstance();
    }
}

/*
 * Easy Access to logging
 * works only if logging service exists
 *
 * override as needed
 */
if (!function_exists('logMsg')) {
    function logMsg(mixed $level, string $msg): void
    {
        if (container()->has('log')) {
            container()->log->log($level, $msg);
        }
    }
}

/* wrapper to read a config value */
if (!function_exists('config')) {
    function config(string $filename, string $key, mixed $default = null): mixed
    {
        $config = $default;

        if (container()->has('config')) {
            $config = container()->config->get($filename, $key, $default);
        }

        return $config;
    }
}

/* wrapper for router get url */
if (!function_exists('getUrl')) {
    function getUrl(): string
    {
        $getUrl = '';

        if (container()->has('router')) {
            $arguments = func_get_args();

            $searchName = array_shift($arguments);

            $getUrl = container()->router->getUrl($searchName, $arguments);
        }

        return $getUrl;
    }
}
