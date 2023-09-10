<?php

declare(strict_types=1);

namespace app\controllers;

use dmyers\orange\interfaces\DataInterface;
use dmyers\orange\interfaces\InputInterface;
use dmyers\orange\interfaces\ConfigInterface;
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

    // auto injection based on variable name is service name
    public function __construct(OutputInterface $output, InputInterface $input, ConfigInterface $config, ViewerInterface $view, DataInterface $data)
    {
        $this->output = $output;
        $this->input = $input;
        $this->config = $config;
        $this->view = $view;
        $this->data = $data;

        if (!empty($this->contentType)) {
            $this->output->contentType($this->contentType);
        }

    }
}
