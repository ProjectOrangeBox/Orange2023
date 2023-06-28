<?php

declare(strict_types=1);

namespace dmyers\orange;

use dmyers\orange\ViewerAbstract;
use dmyers\orange\interfaces\DataInterface;
use dmyers\orange\interfaces\ViewerInterface;

class View extends ViewerAbstract implements ViewerInterface
{
    private static ViewerInterface $instance;

    private function __construct(array $config, ?DataInterface $data = null)
    {
        $this->config = \mergeDefaultConfig($config, __DIR__ . '/config/view.php');
        $this->data = $data;

        $this->setConfiguration();
    }

    public static function getInstance(array $config, ?DataInterface $data = null): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config, $data);
        }

        return self::$instance;
    }
}
