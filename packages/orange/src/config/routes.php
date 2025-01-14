<?php

declare(strict_types=1);

return [
    // when they try to resolve a name to a url skip matching if the name contains
    'getUrlSkip' => false,
    'routes' => [],
    'default routes' => [
        // home page
        ['method' => '*', 'url' => '/', 'callback' => [\orange\framework\controllers\HomeController::class, 'index'], 'name' => 'home'],

        // 404 catch all
        ['method' => '*', 'url' => '(.*)', 'callback' => [\orange\framework\controllers\FourohfourController::class, 'index'], 'name' => 'fourohfour'],
    ],
];
