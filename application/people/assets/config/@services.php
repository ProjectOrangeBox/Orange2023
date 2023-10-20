// sample model

'model.parent' => function (ContainerInterface $container) {
    return parentModel::getInstance([], $container->pdo);
},
'model.childern' => function (ContainerInterface $container) {
    return parentModel::getInstance([], $container->pdo);
},
