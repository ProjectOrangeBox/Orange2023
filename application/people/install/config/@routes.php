// gui - get
['method' => 'get', 'url' => '/people', 'callback' => [\application\people\controllers\MainController::class, 'readList'], 'name' => 'people'],
['method' => 'get', 'url' => '/people/create', 'callback' => [\application\people\controllers\MainController::class, 'createForm'], 'name' => 'people-create'],
['method' => 'get', 'url' => '/people/update/(\d+)', 'callback' => [\application\people\controllers\MainController::class, 'updateForm'], 'name' => 'people-update'],
['method' => 'get', 'url' => '/people/delete/(\d+)', 'callback' => [\application\people\controllers\MainController::class, 'deleteForm'], 'name' => 'people-delete'],

// actions - post
['method' => 'post', 'url' => '/people/create', 'callback' => [\application\people\controllers\MainController::class, 'create'], 'name'=>'people-create-post'],
['method' => 'put', 'url' => '/people/update/(\d+)', 'callback' => [\application\people\controllers\MainController::class, 'update'], 'name'=>'people-update-post'],
['method' => 'delete', 'url' => '/people/delete/(\d+)', 'callback' => [\application\people\controllers\MainController::class, 'delete'], 'name'=>'people-delete-post'],