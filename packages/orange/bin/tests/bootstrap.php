<?php

error_reporting(E_ALL);

define('__ROOT__', realpath(__DIR__ . '/../../../../'));
define('__WWW__', realpath(__DIR__ . '/../../../../htdocs'));

define('DEBUG', true);
define('ENVIRONMENT', 'testing');
define('UNDEFINED', chr(0));
define('UNITTEST', true);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(-1);

$_ENV = array_replace_recursive($_ENV, parse_ini_file(__DIR__ . '/../../tests/support/env', true, INI_SCANNER_TYPED));

// for testing
define('WORKINGDIR', realpath(__DIR__ . '/../../tests/working'));
define('MOCKDIR', realpath(__DIR__ . '/../../tests/mocks'));
define('STUBDIR', realpath(__DIR__ . '/../../src/stubs'));

require __DIR__ . '/../../src/helpers/helpers.php';
require __DIR__ . '/../../src/helpers/wrappers.php';
require __DIR__ . '/../../tests/support/unitTestHelper.php';