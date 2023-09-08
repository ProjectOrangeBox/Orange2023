<?php

declare(strict_types=1);

namespace app\controllers;

use dmyers\orange\interfaces\DataInterface;
use dmyers\orange\interfaces\InputInterface;
use dmyers\orange\interfaces\ConfigInterface;
use dmyers\orange\interfaces\ContainerInterface;
use dmyers\orange\interfaces\OutputInterface;
use dmyers\orange\interfaces\ViewerInterface;

abstract class BaseController
{
    protected OutputInterface $output;
    protected InputInterface $input;
    protected ConfigInterface $config;
    protected ViewerInterface $view;
    protected DataInterface $data;

    protected string $contentType = '';

    public function __construct(ContainerInterface $container)
    {
        $this->input = $container->getService('input');
        $this->output = $container->getService('output');
        $this->config = $container->getService('config');
        $this->view = $container->getService('view');
        $this->data = $container->getService('data');

        if (!empty($this->contentType)) {
            $this->output->contentType($this->contentType);
        }

    }
}
