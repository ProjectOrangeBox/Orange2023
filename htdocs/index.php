<?php

declare(strict_types=1);

// setup the application ROOT
// handy for mocking data instead of hardwired a directory location based on the file
// you can just change __ROOT__ to something else then change it back for example
define('__ROOT__', realpath(__DIR__ . '/../'));

// where is our www directory?
define('__WWW__', realpath(__DIR__));

// merge our .env with the system env
$_ENV = array_replace_recursive($_ENV, parse_ini_file(__ROOT__ . '/.env', true, INI_SCANNER_TYPED));

// load our start up configuration
$config = include __ROOT__ . '/config/config.php';

// our own personal bootstraping file which can also modify $config if necessary
require_once __ROOT__ . '/bootstrap.php';

// composer auto loader
require_once __ROOT__ . '/vendor/autoload.php';

/* send config into http application and away we go! */
\orange\framework\Application::http($config);
