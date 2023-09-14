<?php

declare(strict_types=1);

// a route name is used with the getUrl(...) method
// all routes can have a name but it not required
// Names make it so you can use getUrl(...) to return a url to that route
// if the route changes here you don't need to update all of your code because getUrl(...) handles that automatically
// if you redirect to the path named "foobar" and the "foobar" url changes the route automatically changes

// '*' matchers on all http methods
// 'get' matches only on the get method
// ['get','post'] matches on both get and post method
return [
    // require setting a site url this is used when creating URL for:
    // getUrl(...)
    // siteUrl(...)
    'site' => 'example.local',
    // all of the routes need to be in this array
    'routes' => [
        /* home page */
        ['method' => '*', 'url' => '/', 'callback' => [\app\controllers\MainController::class, 'index'], 'name' => 'home'],

        ['method' => 'get',     'url' => '/rest', 'callback' => [\app\controllers\RestController::class, 'get'],    'name' => 'restGet'],
        ['method' => 'post',    'url' => '/rest', 'callback' => [\app\controllers\RestController::class, 'post'],   'name' => 'restPost'],
        ['method' => 'put',     'url' => '/rest', 'callback' => [\app\controllers\RestController::class, 'put'],    'name' => 'restPut'],
        ['method' => 'delete',  'url' => '/rest', 'callback' => [\app\controllers\RestController::class, 'delete'], 'name' => 'restDelete'],
        ['method' => 'patch',   'url' => '/rest', 'callback' => [\app\controllers\RestController::class, 'patch'],  'name' => 'restPatch'],
        ['method' => 'options', 'url' => '/rest', 'callback' => [\app\controllers\RestController::class, 'options'], 'name' => 'restOptions'],

        ['method' => '*', 'url' => '/missing', 'callback' => [\app\controllers\MainController::class, 'missing'],  'name' => 'missing'],
        ['method' => '*', 'url' => '/redirect', 'callback' => [\app\controllers\MainController::class, 'redirect'], 'name' => 'redirect'],
        ['method' => '*', 'url' => '/error', 'callback' => [\app\controllers\MainController::class, 'error'],    'name' => 'error'],

        ['method' => 'get', 'url' => '/form', 'callback' => [\app\controllers\FormController::class, 'index']],
        ['method' => 'post', 'url' => '/form', 'callback' => [\app\controllers\FormController::class, 'submit']],

        ['method' => 'get', 'url' => '/model', 'callback' => [\app\controllers\ModelController::class, 'index']],

        // example of using modules one named A and another named b
        // these can be treated like individual applications 
        ['method' => 'get', 'url' => '/modulea', 'callback' => [\example\modulea\controllers\MainController::class, 'index']],
        ['method' => 'get', 'url' => '/moduleb', 'callback' => [\example\moduleb\controllers\MainController::class, 'index']],

        /* 404 catch all */
        ['method' => '*', 'url' => '(.*)', 'callback' => [\app\controllers\FourohfourController::class, 'index'], 'name' => 'fourohfour'],

        // these are used to get paths router::getUrl(...)
        // then if you need to change a path you simply need to change it here and not in mutiple files
        ['name' => 'assets', 'url' => '/assets'],
        ['name' => 'product', 'url' => '/product/([a-z]+)/(\d+)'],
        ['name' => 'javascript', 'url' => '/assets/js'],
        ['name' => 'css', 'url', '/assets/css'],
        ['name' => 'images', 'url' => '/images'],
        ['name' => 'home', 'url' => '/'],
    ],
];
