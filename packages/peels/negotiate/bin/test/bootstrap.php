<?php

define('DEBUG', true);
define('ENVIRONMENT', 'testing');

require __DIR__ . '/unitTestHelper.php';

// making these will make it so the defaults won't be loaded
if (!function_exists('orangeExceptionHandler')) {
    function orangeExceptionHandler()
    {
    }
}

if (!function_exists('orangeErrorHandler')) {
    function orangeErrorHandler()
    {
    }
}
