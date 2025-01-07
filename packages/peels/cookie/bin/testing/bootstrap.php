<?php

define('__ROOT__', realpath(__DIR__ . '/../../../../'));
define('__WWW__', realpath(__DIR__ . '/../../../../htdocs'));

$_ENV = array_replace_recursive($_ENV, parse_ini_file(__DIR__ . '/env', true, INI_SCANNER_TYPED));

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

function logMsg()
{
}

if (!function_exists('concat')) {
    function concat(): string
    {
        return implode('', func_get_args());
    }
}

require __DIR__ . '/../../../../../vendor/autoload.php';
require __DIR__ . '/support/unitTestHelper.php';
