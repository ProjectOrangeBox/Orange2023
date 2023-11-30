
'model.people' => function (ContainerInterface $container) {
    return \application\people\models\peopleModel::getInstance([], $container->pdo);
},
