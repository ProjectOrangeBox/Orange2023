'model.people' => function (ContainerInterface $container) {
    return peopleModel::getInstance([], $container->pdo, $container->validate);
},