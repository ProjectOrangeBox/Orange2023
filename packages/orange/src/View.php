<?php

declare(strict_types=1);

namespace dmyers\orange;

use dmyers\orange\ViewerAbstract;
use dmyers\orange\interfaces\DataInterface;
use dmyers\orange\interfaces\ViewerInterface;

class View extends ViewerAbstract implements ViewerInterface
{
    // use all the default methods in viewer abstract

    // new instance of view
    public static function getInstance(array $config, ?DataInterface $data = null): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config, $data);
        }

        return self::$instance;
    }
}
