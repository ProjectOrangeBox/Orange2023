<?php

declare(strict_types=1);

// setup the application ROOT 
// handy for mocking data instead of hardwired a folder location based on the file
// you can just change __ROOT__ to something else then change it back for example
define('__ROOT__', realpath(__DIR__ . '/../'));

// where is our www folder?
define('__WWW__', realpath(__DIR__));

// merge our .env with the system env
$_ENV = array_replace_recursive($_ENV, parse_ini_file(__ROOT__ . '/.env', true, INI_SCANNER_TYPED));

$config = include __ROOT__ . '/config/config.php';

// our own personal bootstraping file which can also modify $config if necessary
require_once __ROOT__ . '/bootstrap.php';

// composer auto loader
require_once __ROOT__ . '/vendor/autoload.php';

/* send config into application and away we go! */
http($config);
