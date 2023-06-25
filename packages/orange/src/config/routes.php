<?php

declare(strict_types=1);

return [
    'site' => null,
    'routes' => [
        // home page
        ['method' => '*', 'url' => '/', 'callback' => [\app\controllers\MainController::class, 'index'], 'name' => 'home'],

        // 404 catch all
        ['method' => '*', 'url' => '(.*)', 'callback' => [\app\controllers\FourohfourController::class, 'index'], 'name' => 'fourohfour'],

        ['name' => 'assets', 'url' => '/assets'],
        ['name' => 'javascript', 'url' => '/assets/js'],
        ['name' => 'css', 'url', '/assets/css'],
        ['name' => 'images', 'url' => '/images'],
    ],
];
