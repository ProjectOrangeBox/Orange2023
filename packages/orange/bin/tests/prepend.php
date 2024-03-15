<?php

define('__ROOT__', realpath(__DIR__ . '/../../../../'));
define('__WWW__', realpath(__DIR__ . '/../../../../htdocs'));

define('DEBUG', true);
define('ENVIRONMENT', 'testing');

$_ENV = array_replace_recursive($_ENV, parse_ini_file(__DIR__ . '/../../tests/env', true, INI_SCANNER_TYPED));

require_once __DIR__.'/../../src/helpers/helpers.php';
require_once __DIR__.'/../../src/helpers/wrappers.php';

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
