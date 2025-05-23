<?php

declare(strict_types=1);

use orange\framework\Application;

return [
    // default unless overridden
    'directories' => [
        Application::configDirectory(),
        Application::configDirectory() . '/' . Application::env('ENVIRONMENT'),
    ],
];
