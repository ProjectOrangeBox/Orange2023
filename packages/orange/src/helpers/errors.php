<?php

declare(strict_types=1);

/*
 * Low level error handling
 */

// 401 Unauthorized
if (!function_exists('show401')) {
    function show401(string $message = ''): void
    {
        throw new \orange\framework\exceptions\http\Http401($message);
    }
}

// 403 Forbidden
if (!function_exists('show403')) {
    function show403(string $message = ''): void
    {
        throw new \orange\framework\exceptions\http\Http403($message);
    }
}

// 404 Not Found
if (!function_exists('show404')) {
    function show404(string $message = ''): void
    {
        throw new \orange\framework\exceptions\http\Http404($message);
    }
}

// 500 Internal Server Error
if (!function_exists('show500')) {
    function show500(string $message = ''): void
    {
        throw new \orange\framework\exceptions\http\Http500($message);
    }
}

// 301 Moved Permanently
if (!function_exists('redirect301')) {
    function redirect301(string $url, string $message = ''): void
    {
        throw new \orange\framework\exceptions\http\Http301($url, $message);
    }
}

/*
 * Convert PHP error to an exception
 */
if (!function_exists('errorHandler')) {
    function errorHandler(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (!(error_reporting() & $errno)) {
            // This error code is not included in error_reporting
            return false;
        }

        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
}

/*
 * default exception handler
 *
 * override as needed
 */
if (!function_exists('exceptionHandler')) {
    function exceptionHandler(Throwable $exception): void
    {
        // make a direct instance of Error Class
        // override this function if you want to use your own class
        \orange\framework\Error::getInstance([], container(), $exception);

        // exit with error safety
        exit(1);
    }
}
