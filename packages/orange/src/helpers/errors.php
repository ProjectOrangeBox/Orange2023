<?php

declare(strict_types=1);

/**
 * Low level error handling
 */

// 401 Unauthorized
if (!function_exists('show401')) {
    function show401(string $msg = 'Unauthorized'): void
    {
        showErrorHalt(401, $msg);
    }
}

// 403 Forbidden
if (!function_exists('show403')) {
    function show403(string $msg = 'Forbidden'): void
    {
        showErrorHalt(403, $msg);
    }
}

// 404 Not Found
if (!function_exists('show404')) {
    function show404(string $msg = 'Not Found'): void
    {
        showErrorHalt(404, $msg);
    }
}

// 500 Internal Server Error
if (!function_exists('show500')) {
    function show500(string $msg = 'Internal Server Error'): void
    {
        showErrorHalt(500, $msg);
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
        showErrorHalt(500, 'Exception', ['message' => $exception->getMessage(), 'code' => $exception->getCode(), 'line' => $exception->getLine(), 'file' => $exception->getFile(), 'class' => get_class($exception), 'trace' => $exception->getTrace()]);
    }
}

/*
 * default error handler
 *
 * override as needed
 */
if (!function_exists('errorHandler')) {
    function errorHandler($severity, $message, $filepath, $line)
    {
        if (!(error_reporting() & $severity)) {
            // This error code is not included in error_reporting, so let it fall
            // through to the standard PHP error handler
            return false;
        }

        showErrorHalt(500, 'Error', compact('severity', 'message', 'filepath', 'line'));
    }
}

/*
 * low level death
 *
 * override for testing since this uses a lot of unmockable
 * hard coded methods & values
 * php_sapi_name(), $_SERVER, http_response_code(), etc...
 */
if (!function_exists('showErrorHalt')) {
    function showErrorHalt(int $errorCode = 500, string $text = '', array $options = [], array $config = []): void
    {
        $output = '';

        // let's try to determine the output type
        if (strtolower(php_sapi_name()) === 'cli' || defined('STDIN')) {
            // CLI
            $contentType = 'cli';
            $folder = 'cli';
        } elseif (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            // ajax / json
            $contentType = 'application/json';
            $folder = 'ajax';
        } else {
            // HTML
            $contentType = 'text/html';
            $folder = 'html';
        }

        // assume worst case it's production - also make lowercase because we use this as a folder in the path
        $env = (defined('ENVIRONMENT')) ? strtolower(ENVIRONMENT) : 'production';

        // if it's a production environment clear out options because they might reveal details
        if ($env == 'production') {
            $options = [];
        }

        if (container()->has('view')) {
            $view = implode(DIRECTORY_SEPARATOR, ['errors', $env, $folder, $errorCode]);

            $viewService = container()->get('view');

            if ($viewService->viewSearch->exists($view)) {
                $output = $viewService->render($view, compact('errorCode', 'text', 'options'));
            }
        }

        if (empty($output)) {
            // can't find a matching view so fall back to hard coded response format
            switch ($folder) {
                case 'ajax':
                    $output .= json_encode(compact('text', 'errorCode', 'options'), JSON_PRETTY_PRINT);
                    break;
                case 'html':
                    $output .= '<h1>' . $errorCode . '</h1>';
                    $output .= (!empty($text)) ? '<h3>' . $text . '</h3>' : '';
                    $output .= (!empty($options)) ? '<pre>' . var_export($options, true) . '</pre>' : '';
                    break;
                default:
                    // cli other?
                    $output .= $errorCode . PHP_EOL;
                    $output .= (!empty($text)) ? $text . PHP_EOL : '';
                    $output .= (!empty($options)) ? var_export($options, true) . PHP_EOL : '';
                    break;
            }
        }

        // don't send any header stuff on a cli error
        if ($contentType != 'cli') {
            http_response_code($errorCode);

            $charSet = $config['char set'] ?? 'UTF-8';

            header('Content-Type:' . $contentType . '; charset=' . $charSet);
        }

        // send to output
        echo $output;

        // fail safe exit "with error"
        exit(1);
    }
}
