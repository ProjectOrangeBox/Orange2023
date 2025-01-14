<?php

declare(strict_types=1);

return [
    // when they try to resolve a name to a url skip matching if the name contains
    'skip checking type' => false,
    'routes' => [],
    'matchAll' => ['get', 'post', 'put', 'delete'],
    // 404 catch all
    '404' => ['method' => '*', 'url' => '(.*)', 'callback' => [\orange\framework\controllers\FourohfourController::class, 'index'], 'name' => 'fourohfour'],
    // home page
    'home' => ['method' => '*', 'url' => '/', 'callback' => [\orange\framework\controllers\HomeController::class, 'index'], 'name' => 'home'],
];
