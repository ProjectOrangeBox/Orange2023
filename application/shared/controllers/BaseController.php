<?php

declare(strict_types=1);

namespace application\shared\controllers;

use stdClass;
use dmyers\orange\interfaces\DataInterface;
use dmyers\orange\interfaces\InputInterface;
use dmyers\orange\interfaces\ConfigInterface;
use dmyers\orange\interfaces\OutputInterface;
use dmyers\orange\interfaces\ViewerInterface;

/**
 * this is a user controller that others can extend it is not nessesary but it's nice to put commonly used code here
 */
abstract class BaseController
{
    protected string $contentType = '';

    protected array $preloadModels = [];
    protected stdClass $model;

    // auto injection based on variable name is service name
    // PHP 8: Constructor property promotion
    public function __construct(public OutputInterface $output, public InputInterface $input, public ConfigInterface $config, public  ViewerInterface $view, public DataInterface $data)
    {
        // change content type if provided
        if (!empty($this->contentType)) {
            $this->output->contentType($this->contentType);
        }

        // preload some models for this controller and attach to model local property
        $this->model = new stdClass();

        foreach ($this->preloadModels as $name => $serverName) {
            $this->model->$name = container()->get($serverName);
        }

        $reflector = new \ReflectionClass(get_class($this));

        // add our local views path
        $this->view->addPath(dirname($reflector->getFileName()).'/../views');
        $this->view->addPath(__DIR__.'/../views');
    }
}
