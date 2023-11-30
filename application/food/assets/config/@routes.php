// gui - get
['method' => 'get', 'url' => '/food', 'callback' => [\application\food\controllers\MainController::class, 'index'], 'name' => 'food'],
['method' => 'get', 'url' => '/food/create', 'callback' => [\application\food\controllers\MainController::class, 'createForm'], 'name' => 'food-create'],
['method' => 'get', 'url' => '/food/update/(\d+)', 'callback' => [\application\food\controllers\MainController::class, 'updateForm'], 'name' => 'food-update'],
['method' => 'get', 'url' => '/food/delete/(\d+)', 'callback' => [\application\food\controllers\MainController::class, 'deleteForm'], 'name' => 'food-delete'],

// actions - post
['method' => 'post', 'url' => '/food/create', 'callback' => [\application\food\controllers\MainController::class, 'create'], 'name'=>'food-create-post'],
['method' => 'put', 'url' => '/food/update/(\d+)', 'callback' => [\application\food\controllers\MainController::class, 'update'], 'name'=>'food-update-post'],
['method' => 'delete', 'url' => '/food/delete/(\d+)', 'callback' => [\application\food\controllers\MainController::class, 'delete'], 'name'=>'food-delete-post'],