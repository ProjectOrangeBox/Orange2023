<?php

declare(strict_types=1);

return [
    // when resolving a name should we skip parameter type checking?
    'skip parameter type checking' => false,
    'routes' => [],
    'match all' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
    // 404 catch all
    '404' => ['method' => '*', 'url' => '(.*)', 'callback' => [\orange\framework\controllers\FourohfourController::class, 'index'], 'name' => 'fourohfour'],
    // home page
    'home' => ['method' => '*', 'url' => '/', 'callback' => [\orange\framework\controllers\HomeController::class, 'index'], 'name' => 'home'],
];
