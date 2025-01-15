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
    'site' => 'orange.local',
    'getUrlSkip' => false,
    // all of the routes need to be in this array
    'routes' => [
        /* home page */
        ['method' => '*', 'url' => '/', 'callback' => [\application\welcome\controllers\MainController::class, 'index'], 'name' => 'home'],

        // application/people/controllers/MainController
        ['method' => 'get', 'url' => '/colordropdown', 'callback' => [\application\people\controllers\MainController::class, 'colordropdown'], 'name' => 'peoplecolordropdown'],
        ['method' => 'get', 'url' => '/peopledropdown', 'callback' => [\application\people\controllers\MainController::class, 'dropdown'], 'name' => 'peopledropdown'],
        ['method' => 'get', 'url' => '/peopledropdown2', 'callback' => [\application\people\controllers\MainController::class, 'dropdown2'], 'name' => 'peopledropdown2'],
        ['method' => 'get', 'url' => '/people', 'callback' => [\application\people\controllers\MainController::class, 'readList'], 'name' => 'peopleReadList'],
        ['method' => 'get', 'url' => '/people/show/(\d+)', 'callback' => [\application\people\controllers\MainController::class, 'readForm'], 'name' => 'peopleReadForm'],
        ['method' => 'get', 'url' => '/people/create', 'callback' => [\application\people\controllers\MainController::class, 'createForm'], 'name' => 'peopleCreateForm'],
        ['method' => 'get', 'url' => '/people/update/(\d+)', 'callback' => [\application\people\controllers\MainController::class, 'updateForm'], 'name' => 'peopleUpdateForm'],
        ['method' => 'get', 'url' => '/people/delete/(\d+)', 'callback' => [\application\people\controllers\MainController::class, 'deleteForm'], 'name' => 'peopleDeleteForm'],
        ['method' => 'get', 'url' => '/people/all', 'callback' => [\application\people\controllers\MainController::class, 'readAll'], 'name' => 'peopleReadAll'],
        ['method' => 'get', 'url' => '/people/(\d+)', 'callback' => [\application\people\controllers\MainController::class, 'readOne'], 'name' => 'peopleReadOne'],
        ['method' => 'get', 'url' => '/people/new', 'callback' => [\application\people\controllers\MainController::class, 'readNew'], 'name' => 'peopleReadNew'],
        ['method' => 'post', 'url' => '/people', 'callback' => [\application\people\controllers\MainController::class, 'create'], 'name' => 'peopleCreate'],
        ['method' => 'put', 'url' => '/people/(\d+)', 'callback' => [\application\people\controllers\MainController::class, 'update'], 'name' => 'peopleUpdate'],
        ['method' => 'delete', 'url' => '/people/(\d+)', 'callback' => [\application\people\controllers\MainController::class, 'delete'], 'name' => 'peopleDelete'],
        // ---------------------------------------------

        /* merge above this line */

        // these are used to get paths router::getUrl(...)
        // then if you need to change a path you simply need to change it here and not in mutiple files
        ['url' => '/assets', 'name' => 'assets'],
        ['url' => '/assets/js', 'name' => 'javascript'],
        ['url' => '/assets/css', 'name' => 'css'],
        ['url' => '/images', 'name' => 'images'],
    ],
];
