<?php

declare(strict_types=1);

return [
    'site' => 'lemon.dvl.to',
    'routes' => [
        /* home page */
        ['method' => '*', 'url' => '/', 'callback' => [\app\controllers\MainController::class, 'index'], 'name' => 'home'],

        ['method' => 'get',     'url' => '/rest', 'callback' => [\app\controllers\RestController::class, 'get'],    'name' => 'restGet'],
        ['method' => 'post',    'url' => '/rest', 'callback' => [\app\controllers\RestController::class, 'post'],   'name' => 'restPost'],
        ['method' => 'put',     'url' => '/rest', 'callback' => [\app\controllers\RestController::class, 'put'],    'name' => 'restPut'],
        ['method' => 'delete',  'url' => '/rest', 'callback' => [\app\controllers\RestController::class, 'delete'], 'name' => 'restDelete'],
        ['method' => 'patch',   'url' => '/rest', 'callback' => [\app\controllers\RestController::class, 'patch'],  'name' => 'restPatch'],
        ['method' => 'options', 'url' => '/rest', 'callback' => [\app\controllers\RestController::class, 'options'],'name' => 'restOptions'],

        ['method' => '*', 'url' => '/missing',  'callback' => [\app\controllers\MainController::class, 'missing'],  'name' => 'missing'],
        ['method' => '*', 'url' => '/redirect', 'callback' => [\app\controllers\MainController::class, 'redirect'], 'name' => 'redirect'],
        ['method' => '*', 'url' => '/error',    'callback' => [\app\controllers\MainController::class, 'error'],    'name' => 'error'],

        ['method' => 'get', 'url' => '/form',    'callback' => [\app\controllers\FormController::class, 'index']],
        ['method' => 'post', 'url' => '/form',    'callback' => [\app\controllers\FormController::class, 'submit']],

        ['method' => 'get', 'url' => '/model', 'callback' => [\app\controllers\ModelController::class, 'index']],

        ['method' => 'get', 'url' => '/modulea', 'callback' => [\example\modulea\controllers\MainController::class, 'index']],
        ['method' => 'get', 'url' => '/moduleb', 'callback' => [\example\moduleb\controllers\MainController::class, 'index']],

        /* 404 catch all */
        ['method' => '*', 'url' => '(.*)', 'callback' => [\app\controllers\FourohfourController::class, 'index'], 'name'=> 'fourohfour'],

        ['name' => 'assets', 'url' => '/assets'],
        ['name' => 'product', 'url' => '/product/([a-z]+)/(\d+)'],
        ['name' => 'javascript', 'url' => '/assets/js'],
        ['name' => 'css', 'url', '/assets/css'],
        ['name' => 'images', 'url' => '/images'],
        ['name' => 'home', 'url' => '/'],
    ],
];
