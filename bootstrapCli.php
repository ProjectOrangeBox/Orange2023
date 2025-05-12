<?php

use orange\framework\Application;

// setup the application ROOT
// handy for mocking data instead of hardwired a directory location based on the file
// you can just change __ROOT__ to something else then change it back for example
define('__ROOT__', realpath(__DIR__));

// merge our .env with the system env - these could be cli specific
$_ENV = array_replace_recursive($_ENV, parse_ini_file(__ROOT__ . '/.env', true, INI_SCANNER_TYPED));

// load our start up configuration
$config = include __ROOT__ . '/config/config.php';

$config['services file'] = __ROOT__ . '/config/servicesCli.php';

// Do any additional bootstrap here you have access to the config file contents

// composer auto loader
require_once __ROOT__ . '/vendor/autoload.php';

/* send config into cli application and away we go! */
$container = Application::cli($config);