
'model.food' => function (ContainerInterface $container) {
    return \application\food\models\foodModel::getInstance([], $container->pdo);
},
