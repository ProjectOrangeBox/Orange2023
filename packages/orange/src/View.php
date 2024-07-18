<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\ViewAbstract;
use orange\framework\interfaces\DataInterface;
use orange\framework\interfaces\ViewInterface;
use orange\framework\interfaces\RouterInterface;

class View extends ViewAbstract implements ViewInterface
{
    protected static ?ViewInterface $instance = null;

    public static function getInstance(array $config, ?DataInterface $data = null, ?RouterInterface $router = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config, $data, $router);
        }

        return self::$instance;
    }
}
