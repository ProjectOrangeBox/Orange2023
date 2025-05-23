<?php

declare(strict_types=1);

use orange\framework\Application;

// All directories are based off of this root path and
// everything goes under this path for security and easier portability
define('__ROOT__', realpath(__DIR__ . '/../'));

// where is our public www directory?
define('__WWW__', __ROOT__ . '/htdocs');

// load the standard composer autoloader
require_once __ROOT__ . '/vendor/autoload.php';

// Load the application environment
Application::load(__ROOT__ . '/.env');

// let the application know where the config files are and away we go!
Application::http(__ROOT__ . '/config');
