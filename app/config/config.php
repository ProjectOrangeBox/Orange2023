<?php

declare(strict_types=1);

return [
    'config folder' => __ROOT__ . '/app/config',
    'environment' => fetchEnv('ENVIRONMENT', 'production'),
    'debug' => fetchEnv('DEBUG', false),
    'services' => __ROOT__ . '/app/config/services.php',
    'bootstrap file' => __ROOT__ . '/app/Bootstrap.php',
];
