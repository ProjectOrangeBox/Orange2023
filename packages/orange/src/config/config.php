<?php

declare(strict_types=1);

use orange\framework\Application;

return [
    // default unless overridden
    'config directory search' => [
        __ROOT__ . '/config',
        __ROOT__ . '/config/' . Application::env('ENVIRONMENT'),
    ],
];
