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

        ['method' => '*', 'url' => '/missing', 'callback' => [\application\welcome\controllers\MainController::class, 'missing']],
        ['method' => '*', 'url' => '/redirect', 'callback' => [\application\welcome\controllers\MainController::class, 'redirect']],
        ['method' => '*', 'url' => '/error', 'callback' => [\application\welcome\controllers\MainController::class, 'error']],

        // application/join/controllers/MainController
        ['method' => 'get', 'url' => '/join', 'name' => 'joinReadList', 'callback' => [\application\join\controllers\MainController::class, 'readList']],
        ['method' => 'get', 'url' => '/join/show/(\d+)', 'name' => 'joinReadForm', 'callback' => [\application\join\controllers\MainController::class, 'readForm']],
        ['method' => 'get', 'url' => '/join/create', 'name' => 'joinCreateForm', 'callback' => [\application\join\controllers\MainController::class, 'createForm']],
        ['method' => 'get', 'url' => '/join/update/(\d+)', 'name' => 'joinUpdateForm', 'callback' => [\application\join\controllers\MainController::class, 'updateForm']],
        ['method' => 'get', 'url' => '/join/delete/(\d+)', 'name' => 'joinDeleteForm', 'callback' => [\application\join\controllers\MainController::class, 'deleteForm']],
        ['method' => 'get', 'url' => '/join/all', 'name' => 'joinReadAll', 'callback' => [\application\join\controllers\MainController::class, 'readAll']],
        ['method' => 'get', 'url' => '/join/(\d+)', 'name' => 'joinReadOne', 'callback' => [\application\join\controllers\MainController::class, 'readOne']],
        ['method' => 'post', 'url' => '/join', 'name' => 'joinCreate', 'callback' => [\application\join\controllers\MainController::class, 'create']],
        ['method' => 'put', 'url' => '/join/(\d+)', 'name' => 'joinUpdate', 'callback' => [\application\join\controllers\MainController::class, 'update']],
        ['method' => 'delete', 'url' => '/join/(\d+)', 'name' => 'joinDelete', 'callback' => [\application\join\controllers\MainController::class, 'delete']],
        // -------------------------------------------

        // application/people/controllers/MainController
        ['method' => 'get', 'url' => '/colordropdown', 'name' => 'peoplecolordropdown', 'callback' => [\application\people\controllers\MainController::class, 'colordropdown']],
        ['method' => 'get', 'url' => '/peopledropdown', 'name' => 'peopledropdown', 'callback' => [\application\people\controllers\MainController::class, 'dropdown']],
        ['method' => 'get', 'url' => '/peopledropdown2', 'name' => 'peopledropdown2', 'callback' => [\application\people\controllers\MainController::class, 'dropdown2']],
        ['method' => 'get', 'url' => '/people', 'name' => 'peopleReadList', 'callback' => [\application\people\controllers\MainController::class, 'readList']],
        ['method' => 'get', 'url' => '/people/show/(\d+)', 'name' => 'peopleReadForm', 'callback' => [\application\people\controllers\MainController::class, 'readForm']],
        ['method' => 'get', 'url' => '/people/create', 'name' => 'peopleCreateForm', 'callback' => [\application\people\controllers\MainController::class, 'createForm']],
        ['method' => 'get', 'url' => '/people/update/(\d+)', 'name' => 'peopleUpdateForm', 'callback' => [\application\people\controllers\MainController::class, 'updateForm']],
        ['method' => 'get', 'url' => '/people/delete/(\d+)', 'name' => 'peopleDeleteForm', 'callback' => [\application\people\controllers\MainController::class, 'deleteForm']],
        ['method' => 'get', 'url' => '/people/all', 'name' => 'peopleReadAll', 'callback' => [\application\people\controllers\MainController::class, 'readAll']],
        ['method' => 'get', 'url' => '/people/(\d+)', 'name' => 'peopleReadOne', 'callback' => [\application\people\controllers\MainController::class, 'readOne']],
        ['method' => 'get', 'url' => '/people/new', 'name' => 'peopleReadNew', 'callback' => [\application\people\controllers\MainController::class, 'readNew']],
        ['method' => 'post', 'url' => '/people', 'name' => 'peopleCreate', 'callback' => [\application\people\controllers\MainController::class, 'create']],
        ['method' => 'put', 'url' => '/people/(\d+)', 'name' => 'peopleUpdate', 'callback' => [\application\people\controllers\MainController::class, 'update']],
        ['method' => 'delete', 'url' => '/people/(\d+)', 'name' => 'peopleDelete', 'callback' => [\application\people\controllers\MainController::class, 'delete']],
        // ---------------------------------------------

        // application/acl/controllers/PermissionController
        ['method' => 'get', 'url' => '/acl/permission', 'callback' => [\application\acl\controllers\PermissionController::class, 'index']],
        ['method' => 'get', 'url' => '/acl/permission/create', 'callback' => [\application\acl\controllers\PermissionController::class, 'createForm']],
        ['method' => 'get', 'url' => 'acl/permission/update/(\d+)', 'callback' => [\application\acl\controllers\PermissionController::class, 'updateForm']],
        ['method' => 'get', 'url' => '/acl/permission/delete', 'callback' => [\application\acl\controllers\PermissionController::class, 'deleteModal']],
        ['method' => 'get', 'url' => '/acl/permission/([a-z|\d+]+)', 'callback' => [\application\acl\controllers\PermissionController::class, 'read']],
        ['method' => 'post', 'url' => '/acl/permission', 'callback' => [\application\acl\controllers\PermissionController::class, 'create']],
        ['method' => 'put', 'url' => '/acl/permission/(\d+)', 'callback' => [\application\acl\controllers\PermissionController::class, 'update']],
        ['method' => 'delete', 'url' => '/acl/permission/(\d+)', 'callback' => [\application\acl\controllers\PermissionController::class, 'delete']],
        // ------------------------------------------------

        // application/child/controllers/MainController
        ['method' => 'get', 'url' => '/child', 'name' => 'child_get_read_list', 'callback' => [\application\child\controllers\MainController::class, 'readList']],
        ['method' => 'get', 'url' => '/child/show/(\d+)', 'name' => 'child_get_read_form', 'callback' => [\application\child\controllers\MainController::class, 'readForm']],
        ['method' => 'get', 'url' => '/child/create', 'name' => 'child_get_create_form', 'callback' => [\application\child\controllers\MainController::class, 'createForm']],
        ['method' => 'get', 'url' => '/child/update/(\d+)', 'name' => 'child_update', 'callback' => [\application\child\controllers\MainController::class, 'updateForm']],
        ['method' => 'get', 'url' => '/child/delete/(\d+)', 'name' => 'child_delete', 'callback' => [\application\child\controllers\MainController::class, 'deleteForm']],
        ['method' => 'get', 'url' => '/child/all', 'name' => 'child_all', 'callback' => [\application\child\controllers\MainController::class, 'readAll']],
        ['method' => 'get', 'url' => '/child/(\d+)', 'name' => 'child_one', 'callback' => [\application\child\controllers\MainController::class, 'readOne']],
        ['method' => 'post', 'url' => '/child', 'name' => 'child_post', 'callback' => [\application\child\controllers\MainController::class, 'create']],
        ['method' => 'put', 'url' => '/child/(\d+)', 'name' => 'child_put', 'callback' => [\application\child\controllers\MainController::class, 'update']],
        ['method' => 'delete', 'url' => '/child/(\d+)', 'name' => 'child_delete', 'callback' => [\application\child\controllers\MainController::class, 'delete']],
        // --------------------------------------------

        /* end merged above this line */

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

