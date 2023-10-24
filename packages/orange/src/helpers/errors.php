<?php

declare(strict_types=1);

/**
 * default exception handler
 *
 * override as needed
 */
if (!function_exists('orangeExceptionHandler')) {
    function orangeExceptionHandler(Throwable $exception): void
    {
        $args = [
            $exception->getMessage(),
            $exception->getCode(),
            $exception->getLine(),
            $exception->getFile(),
            get_class($exception),
        ];

        _lowleveldeath(implode(' ', $args), 500, $exception->getTrace());
    }

    set_exception_handler('orangeExceptionHandler');
}

/**
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

        _lowleveldeath('', 500, func_get_args());
    }

    set_error_handler('orangeErrorHandler');
}

/**
 * low level death
 * handles throwing a error before error service might be setup
 */
if (!function_exists('_lowleveldeath')) {
    function _lowleveldeath(string $text = '', int $errorCode = 500, array $options = []): void
    {
        $write = '';

        if (php_sapi_name() === 'cli') {
            // CLI
            $write .= $errorCode . PHP_EOL;
            $write .= (!empty($text)) ? $text . PHP_EOL : '';
            $write .= (!empty($options)) ? print_r($options, true) . PHP_EOL : '';
        } elseif (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            // ajax / json
            $write .= json_encode(['text' => $text, 'errorCode' => $errorCode, 'options' => $options], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        } else {
            // HTML
            $write .= (!empty($text)) ? '<h1>' . $text . '</h1>' : '';
            $write .= '<h3>' . $errorCode . '</h3>';
            $write .= (!empty($options)) ? '<pre>' . print_r($options, true) . '</pre>' : '';
        }

        try {
            container()->output->flushAll()->status($errorCode)->write($write)->send();
        } catch (Throwable $t) {
            echo $write;
            exit(1);
        }

        // fail safe
        exit(1);
    }
}

if (!function_exists('throw404')) {
    function throw404(string $msg = ''): void
    {
        _lowleveldeath($msg, 404);
    }
}

if (!function_exists('throw500')) {
    function throw500(string $msg = ''): void
    {
        _lowleveldeath($msg, 500);
    }
}
