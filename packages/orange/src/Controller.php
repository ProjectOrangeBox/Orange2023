<?php

declare(strict_types=1);

namespace dmyers\orange;

use dmyers\orange\interfaces\InputInterface;
use dmyers\orange\interfaces\ConfigInterface;
use dmyers\orange\interfaces\OutputInterface;

/**
 * default controller to extend
 */
abstract class Controller
{
    protected OutputInterface $output;
    protected InputInterface $input;
    protected ConfigInterface $config;

    public function __construct(InputInterface $input, OutputInterface $output, ConfigInterface $config)
    {
        $this->input = $input;
        $this->output = $output;
        $this->config = $config;

        $this->_construct();
    }

    protected function _construct()
    {
        // to be overridden by child class
    }
}
