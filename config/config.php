<?php

declare(strict_types=1);

return [
    // where all of the configuration files are stored
    'config folder' => __ROOT__ . '/config',
    // the current environment DEVELOPMENT, TESTING, PRODUCTION, UNITTEST, UAT
    'environment' => fetchAppEnv('ENVIRONMENT', 'production'),
    // global flag to indicate debugging
    'debug' => fetchAppEnv('DEBUG', false),
    // where is the services file
    'services' => __ROOT__ . '/config/services.php',

    'timezone' => @date_default_timezone_get(),

    'encoding' => 'UTF-8',
];
