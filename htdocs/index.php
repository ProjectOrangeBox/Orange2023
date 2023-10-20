<?php

declare(strict_types=1);

// setup the application ROOT 
// handy for mocking data instead of hardwiring a folder location
// you can just change __ROOT__ to something else then change it back for example
define('__ROOT__', realpath(__DIR__ . '/../'));
define('__WWW__', realpath(__DIR__));

// change default directory to the ROOT folder when this file runs
chdir(__ROOT__);

// standard composer auto loader
require_once __ROOT__ . '/vendor/autoload.php';

// merge the file system .env with the system level $_ENV
// you than must use fetchAppEnv() to retrieve values
mergeEnv(__ROOT__.'/.env');

// user added
if (file_exists(__ROOT__.'/bootstrap.php')) {
    require_once __ROOT__.'/bootstrap.php';
}

/* send config into application and away we go! */
http(include __ROOT__ . '/config/config.php');
