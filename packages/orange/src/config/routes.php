<?php

declare(strict_types=1);

return [
    'site' => null,
    'isHttps' => true,
    'getUrlSkip' => ['#', '@', '*', '$'],
    'routes' => [],
    'default routes' => [
        // home page
        ['method' => '*', 'url' => '/', 'callback' => [\orange\framework\controllers\HomeController::class, 'index'], 'name' => 'home'],

        // 404 catch all
        ['method' => '*', 'url' => '(.*)', 'callback' => [orange\framework\controllers\FourohfourController::class, 'index'], 'name' => 'fourohfour'],

        ['name' => 'assets', 'url' => '/assets'],
        ['name' => 'javascript', 'url' => '/assets/js'],
        ['name' => 'css', 'url', '/assets/css'],
        ['name' => 'images', 'url' => '/images'],
    ],
];
