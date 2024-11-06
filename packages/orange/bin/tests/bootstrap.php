<?php

define('__ROOT__', realpath(__DIR__ . '/../../../../'));
define('__WWW__', realpath(__DIR__ . '/../../../../htdocs'));

define('DEBUG', true);
define('ENVIRONMENT', 'testing');
define('UNDEFINED', chr(0));

$_ENV = array_replace_recursive($_ENV, parse_ini_file(__DIR__ . '/../../tests/support/env', true, INI_SCANNER_TYPED));

define('WORKINGFOLDER', realpath(__DIR__ . '/../../tests/working'));
define('MOCKFOLDER', realpath(__DIR__ . '/../../tests/mocks'));
define('STUBFOLDER', realpath(__DIR__ . '/../../src/stubs'));

require_once __DIR__ . '/../../src/helpers/helpers.php';
require_once __DIR__ . '/../../src/helpers/wrappers.php';

// making these will make it so the defaults won't be loaded
if (!function_exists('orangeExceptionHandler')) {
    function orangeExceptionHandler() {}
}

if (!function_exists('orangeErrorHandler')) {
    function orangeErrorHandler() {}
}

require __DIR__ . '/../../tests/support/unitTestHelper.php';
