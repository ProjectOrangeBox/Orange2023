<?php

declare(strict_types=1);

use orange\framework\Router;
use orange\framework\interfaces\RouterInterface;
use orange\framework\interfaces\ContainerInterface;

return [
    'router' => function (ContainerInterface $container): RouterInterface {
        return Router::getInstance($container->config->routes, $container->input, $container->phpcache);
    },
];
