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

        ['method' => '*', 'url' => '/missing', 'callback' => [\application\welcome\controllers\MainController::class, 'missing']],
        ['method' => '*', 'url' => '/redirect', 'callback' => [\application\welcome\controllers\MainController::class, 'redirect']],
        ['method' => '*', 'url' => '/error', 'callback' => [\application\welcome\controllers\MainController::class, 'error']],

        ['method' => '*', 'url' => '/user', 'callback' => [\application\welcome\controllers\TestController::class, 'user']],

        ['method' => 'get', 'url' => '/upload', 'callback' => [\application\welcome\controllers\TestController::class, 'uploadForm']],
        ['method' => 'post', 'url' => '/uploadprocess', 'callback' => [\application\welcome\controllers\TestController::class, 'uploadProcess']],

        ['method' => 'get', 'url' => '/handlebars', 'callback' => [\application\welcome\controllers\TestController::class, 'index']],

        /* merged content below */

        // gui - get
        ['name' => 'peopleController', 'url' => '/people/([a-z]+)'],

        ['method' => 'get', 'url' => '/people', 'callback' => [\application\people\controllers\MainController::class, 'readList'], 'name' => 'people'],
        ['method' => 'get', 'url' => '/people/show/(\d+)', 'callback' => [\application\people\controllers\MainController::class, 'readForm'], 'name' => 'people_show'],
        ['method' => 'get', 'url' => '/people/create', 'callback' => [\application\people\controllers\MainController::class, 'createForm'], 'name' => 'people_create'],
        ['method' => 'get', 'url' => '/people/update/(\d+)', 'callback' => [\application\people\controllers\MainController::class, 'updateForm'], 'name' => 'people_update'],
        ['method' => 'get', 'url' => '/people/delete/(\d+)', 'callback' => [\application\people\controllers\MainController::class, 'deleteForm'], 'name' => 'people_delete'],

        // rest
        ['method' => 'get', 'url' => '/people/all', 'callback' => [\application\people\controllers\MainController::class, 'readAll'], 'name' => 'people_all'],
        ['method' => 'get', 'url' => '/people/(\d+)', 'callback' => [\application\people\controllers\MainController::class, 'readOne'], 'name' => 'people_one'],
        ['method' => 'post', 'url' => '/people', 'callback' => [\application\people\controllers\MainController::class, 'create'], 'name' => 'people_post'],
        ['method' => 'put', 'url' => '/people/(\d+)', 'callback' => [\application\people\controllers\MainController::class, 'update'], 'name' => 'people_put'],
        ['method' => 'delete', 'url' => '/people/(\d+)', 'callback' => [\application\people\controllers\MainController::class, 'delete'], 'name' => 'people_del'],
        /* end merged contents */

        // user
        ['method' => 'get', 'url' => '/acl/user', 'callback' => [\application\acl\controllers\UserController::class, 'index']],
        ['method' => 'get', 'url' => '/acl/user/create', 'callback' => [\application\acl\controllers\UserController::class, 'createForm']],
        ['method' => 'get', 'url' => '/acl/user/update', 'callback' => [\application\acl\controllers\UserController::class, 'updateForm']],
        ['method' => 'get', 'url' => '/acl/user/delete', 'callback' => [\application\acl\controllers\UserController::class, 'deleteModal']],

        ['method' => 'get', 'url' => '/acl/user/([a-z]+)', 'callback' => [\application\acl\controllers\UserController::class, 'read']],
        ['method' => 'get', 'url' => '/acl/user/(\d+)', 'callback' => [\application\acl\controllers\UserController::class, 'read']],

        ['method' => 'post', 'url' => '/acl/user', 'callback' => [\application\acl\controllers\UserController::class, 'create']],
        ['method' => 'put', 'url' => '/acl/user/(\d+)', 'callback' => [\application\acl\controllers\UserController::class, 'update']],
        ['method' => 'delete', 'url' => '/acl/user/(\d+)', 'callback' => [\application\acl\controllers\UserController::class, 'delete']],

        // role
        ['method' => 'get', 'url' => '/acl/role', 'callback' => [\application\acl\controllers\RoleController::class, 'index']],
        ['method' => 'get', 'url' => '/acl/role/create', 'callback' => [\application\acl\controllers\RoleController::class, 'createForm']],
        ['method' => 'get', 'url' => '/acl/role/update', 'callback' => [\application\acl\controllers\RoleController::class, 'updateForm']],
        ['method' => 'get', 'url' => '/acl/role/delete', 'callback' => [\application\acl\controllers\RoleController::class, 'deleteModal']],

        ['method' => 'get', 'url' => '/acl/role/([a-z]+)', 'callback' => [\application\acl\controllers\RoleController::class, 'read']],
        ['method' => 'get', 'url' => '/acl/role/(\d+)', 'callback' => [\application\acl\controllers\RoleController::class, 'read']],

        ['method' => 'post', 'url' => '/acl/role', 'callback' => [\application\acl\controllers\RoleController::class, 'create']],
        ['method' => 'put', 'url' => '/acl/role/(\d+)', 'callback' => [\application\acl\controllers\RoleController::class, 'update']],
        ['method' => 'delete', 'url' => '/acl/role/(\d+)', 'callback' => [\application\acl\controllers\RoleController::class, 'delete']],

        // permission
        ['method' => 'get', 'url' => '/acl/permission', 'callback' => [\application\acl\controllers\PermissionController::class, 'index']],
        ['method' => 'get', 'url' => '/acl/permission/create', 'callback' => [\application\acl\controllers\PermissionController::class, 'createForm']],
        ['method' => 'get', 'url' => '/acl/permission/update/(\d+)', 'callback' => [\application\acl\controllers\PermissionController::class, 'updateForm']],
        ['method' => 'get', 'url' => '/acl/permission/delete', 'callback' => [\application\acl\controllers\PermissionController::class, 'deleteModal']],

        ['method' => 'get', 'url' => '/acl/permission/([a-z]+)', 'callback' => [\application\acl\controllers\PermissionController::class, 'read']],
        ['method' => 'get', 'url' => '/acl/permission/(\d+)', 'callback' => [\application\acl\controllers\PermissionController::class, 'read']],

        ['method' => 'post', 'url' => '/acl/permission', 'callback' => [\application\acl\controllers\PermissionController::class, 'create']],
        ['method' => 'put', 'url' => '/acl/permission/(\d+)', 'callback' => [\application\acl\controllers\PermissionController::class, 'update']],
        ['method' => 'delete', 'url' => '/acl/permission/(\d+)', 'callback' => [\application\acl\controllers\PermissionController::class, 'delete']],


        /* 404 catch all */
        ['method' => '*', 'url' => '(.*)', 'callback' => [\orange\framework\controllers\FourohfourController::class, 'index'], 'name' => 'fourohfour'],

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
