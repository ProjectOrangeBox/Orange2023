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
    'site' => 'git.local',
    // all of the routes need to be in this array
    'routes' => [
        /* home page */
        ['method' => '*', 'url' => '/', 'callback' => [\application\welcome\controllers\MainController::class, 'index'], 'name' => 'home'],

        ['method' => '*', 'url' => '/missing', 'callback' => [\app\controllers\MainController::class, 'missing'],  'name' => 'missing'],
        ['method' => '*', 'url' => '/redirect', 'callback' => [\app\controllers\MainController::class, 'redirect'], 'name' => 'redirect'],
        ['method' => '*', 'url' => '/error', 'callback' => [\app\controllers\MainController::class, 'error'],    'name' => 'error'],

        /* merged content below */

        // gui - get
        ['method' => 'get', 'url' => '/people', 'callback' => [\application\people\controllers\MainController::class, 'index'], 'name' => 'people'],
        ['method' => 'get', 'url' => '/people/create', 'callback' => [\application\people\controllers\MainController::class, 'createForm'], 'name' => 'people-create'],
        ['method' => 'get', 'url' => '/people/update/(\d+)', 'callback' => [\application\people\controllers\MainController::class, 'updateForm'], 'name' => 'people-update'],
        ['method' => 'get', 'url' => '/people/delete/(\d+)', 'callback' => [\application\people\controllers\MainController::class, 'deleteForm'], 'name' => 'people-delete'],

        // actions - post
        ['method' => 'post', 'url' => '/people/create', 'callback' => [\application\people\controllers\MainController::class, 'create'], 'name' => 'people-create-post'],
        ['method' => 'post', 'url' => '/people/update/(\d+)', 'callback' => [\application\people\controllers\MainController::class, 'update'], 'name' => 'people-update-post'],
        ['method' => 'post', 'url' => '/people/delete/(\d+)', 'callback' => [\application\people\controllers\MainController::class, 'delete'], 'name' => 'people-delete-post'],
        /* end merged contents */

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
