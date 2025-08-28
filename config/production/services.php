<?php

declare(strict_types=1);

use orange\framework\Config;
use orange\framework\Router;
use orange\framework\interfaces\ConfigInterface;
use orange\framework\interfaces\RouterInterface;
use orange\framework\interfaces\ContainerInterface;

return [
    'router' => fn(ContainerInterface $container): RouterInterface => Router::getInstance($container->config->routes, $container->input, $container->phpcache),
    'config' => fn(ContainerInterface $container): ConfigInterface => Config::getInstance($container->get('$application.config directories'), $container->phpcache),
];
