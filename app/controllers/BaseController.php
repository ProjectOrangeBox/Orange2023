<?php

declare(strict_types=1);

namespace app\controllers;

use dmyers\orange\Container;
use dmyers\orange\Controller;
use dmyers\orange\interfaces\DataInterface;
use dmyers\orange\interfaces\InputInterface;
use dmyers\orange\interfaces\ConfigInterface;
use dmyers\orange\interfaces\OutputInterface;
use dmyers\orange\interfaces\ViewerInterface;

abstract class BaseController extends Controller
{
    protected OutputInterface $output;
    protected InputInterface $input;
    protected ConfigInterface $config;
    protected ViewerInterface $view;
    protected DataInterface $data;

    public function __construct(InputInterface $input, OutputInterface $output, ConfigInterface $config)
    {
        parent::__construct($input, $output, $config);

        $this->input = $input;
        $this->output = $output;
        $this->config = $config;
        $this->view = Container::getService('view');
        $this->data = Container::getService('data');
    }
}
