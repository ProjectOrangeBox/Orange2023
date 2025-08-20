<?php

declare(strict_types=1);

use orange\framework\Application;

// setup the application ROOT
// handy for mocking data instead of hardwired a directory location based on the file
// you can just change __ROOT__ to something else then change it back for example
define('__ROOT__', __DIR__);

// composer auto loader
require_once __ROOT__ . '/vendor/autoload.php';

Application::loadEnvironment(__ROOT__ . '/.env', __ROOT__ . '/.env-cli');

/* send config into cli application and away we go! */
return Application::cli();
