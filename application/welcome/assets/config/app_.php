<?php

declare(strict_types=1);

return [
    // where all of the configuration files are stored
    'config folder' => __ROOT__ . '/config',
    // the current environment DEVELOPMENT, TESTING, PRODUCTION, UNITTEST, UAT
    'environment' => fetchEnv('ENVIRONMENT', 'production'),
    // global flag to indicate debugging
    'debug' => fetchEnv('DEBUG', false),
    // where is the services file
    'services' => __ROOT__ . '/config/services.php',
];
