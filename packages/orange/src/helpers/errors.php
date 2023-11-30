<?php

declare(strict_types=1);

/* throw a 404 or 500 error - low level */
if (!function_exists('throw404')) {
    function throw404(string $msg = ''): void
    {
        _lowleveldeath(404, $msg);
    }
}

if (!function_exists('throw500')) {
    function throw500(string $msg = ''): void
    {
        _lowleveldeath(500, $msg);
    }
}

/*
 * default exception handler
 *
 * override as needed
 */
if (!function_exists('orangeExceptionHandler')) {
    function orangeExceptionHandler(Throwable $exception): void
    {
        _lowleveldeath(500, 'Exception', ['message' => $exception->getMessage(), 'code' => $exception->getCode(), 'line' => $exception->getLine(), 'file' => $exception->getFile(), 'class' => get_class($exception), 'trace' => $exception->getTrace()]);
    }

    set_exception_handler('orangeExceptionHandler');
}

/*
 * default error handler
 *
 * override as needed
 */
if (!function_exists('orangeErrorHandler')) {
    function orangeErrorHandler($severity, $message, $filepath, $line)
    {
        if (!(error_reporting() & $severity)) {
            // This error code is not included in error_reporting, so let it fall
            // through to the standard PHP error handler
            return false;
        }

        _lowleveldeath(500, 'Error', compact('severity', 'message', 'filepath', 'line'));
    }

    set_error_handler('orangeErrorHandler');
}



/*
 * low level death
 * 
 * override for testing since this uses a lot of unmockable:
 * php_sapi_name(), $_SERVER, uses http_response_code()
 */
if (!function_exists('_lowleveldeath')) {
    function _lowleveldeath(int $errorCode = 500, string $text = '', array $options = []): void
    {
        $write = '';
        $folder = '';

        // this is pretty low level so we just make the "default" output here to 
        if (fetchAppEnv('ENVIRONMENT', 'production') != 'production') {
            if (strtolower(php_sapi_name()) === 'cli' || defined('STDIN')) {
                // CLI
                $folder = 'cli/';
                $write .= $errorCode . PHP_EOL;
                $write .= (!empty($text)) ? $text . PHP_EOL : '';
                $write .= (!empty($options)) ? var_export($options, true) . PHP_EOL : '';
            } elseif (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                // ajax / json
                $folder = 'ajax/';
                $write .= json_encode(compact('text', 'errorCode', 'options'), JSON_PRETTY_PRINT);
            } else {
                // HTML
                $folder = 'html/';
                $write .= '<h1>' . $errorCode . '</h1>';
                $write .= (!empty($text)) ? '<h3>' . $text . '</h3>' : '';
                $write .= (!empty($options)) ? '<pre>' . var_export($options, true) . '</pre>' : '';
            }
        } else {
            $write .= 'Fatal Error: ' . $errorCode . ' ' . $text;

            // clear the options if it's a production env
            $options = [];
        }

        try {
            // let's try to use a view if we can find it
            if (container()->view->findView('errors/' . $folder . $errorCode)) {
                // use the template output
                $write = container()->view->render('errors/' . $folder . $errorCode, compact('errorCode', 'text', 'options'));
            }
        } catch (Throwable $throwable) {
            // do nothing special it will just fall back to the original $write output
        }

        // lowest level output
        if ($folder != 'cli/') {
            http_response_code($errorCode);
        }
        echo $write;

        // fail safe
        exit(1);
    }
}
