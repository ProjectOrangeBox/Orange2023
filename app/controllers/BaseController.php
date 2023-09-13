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
    protected string $contentType = '';
    protected string $prependViewPath = '';

    // auto injection based on variable name is service name
    // PHP 8: Constructor property promotion
    public function __construct(public OutputInterface $output,public InputInterface $input, public ConfigInterface $config,public  ViewerInterface $view, public DataInterface $data)
    {
        $this->output = $output;
        $this->input = $input;
        $this->config = $config;
        $this->view = $view;
        $this->data = $data;

        if (!empty($this->contentType)) {
            $this->output->contentType($this->contentType);
        }

        if (!empty($this->prependViewPath)) {
            $this->view->addPath($this->prependViewPath, true);
        }
    }
}
