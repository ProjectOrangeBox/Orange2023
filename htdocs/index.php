<?php

declare(strict_types=1);

// All directories are based off of this root path and
// everything goes under this path for security and easier portability
define('__ROOT__', realpath(__DIR__ . '/../'));

// where is our public www directory?
define('__WWW__', __ROOT__ . '/htdocs');

// we now merge our system properties with our .env properties
// these make it easier to have specific properties based on the system
// you can make sure for example developers have different properties then say production
$_ENV = array_replace_recursive($_ENV, parse_ini_file(__ROOT__ . '/.env', true, INI_SCANNER_TYPED));

// now we load up our most basic start up configuration
$config = include __ROOT__ . '/config/config.php';

// call our own personal bootstraping file which can also modify $config if necessary
require_once __ROOT__ . '/bootstrap.php';

// load the standard composer autoloader
require_once __ROOT__ . '/vendor/autoload.php';

// send the basic config into our http application and away we go!
\orange\framework\Application::http($config);