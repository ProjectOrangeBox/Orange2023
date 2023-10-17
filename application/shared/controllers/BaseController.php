<?php

declare(strict_types=1);

namespace application\shared\controllers;

use stdClass;
use dmyers\orange\exceptions\FileNotFound;
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

    // global functions
    protected array $helpers = [];
    
    // attached to $this object
    protected array $services = [];

    // attached to $model (see below);
    protected array $models = [];

    // attach models here
    protected stdClass $model;

    // auto injection based on variable name is service name
    public function __construct(public OutputInterface $output, public InputInterface $input, public ConfigInterface $config, public  ViewerInterface $view, public DataInterface $data)
    {
        // ** PHP 8: Constructor property promotion output, input, config, view, data

        // change content type if provided
        if (!empty($this->contentType)) {
            $this->output->contentType($this->contentType);
        }

        // preload some models for this controller and attach to model local property
        $this->model = new stdClass();

        foreach ($this->models as $name => $serviceName) {
            // throws it's own exception if service not found
            $this->model->$name = container()->get($serviceName);
        }

        foreach ($this->helpers as $filename) {
            $helperFilePath = __DIR__.'/helpers/'.$filename.'.php';
            
            if (!file_exists($helperFilePath)) {
                throw new FileNotFound($helperFilePath);
            }

            include $helperFilePath;
        }

        foreach ($this->models as $name => $serviceName) {
            // throws it's own exception if service not found
            $this->$name = container()->get($serviceName);
        }

        $reflector = new \ReflectionClass(get_class($this));

        // add this base controllers local views path
        $this->view->addPath(dirname($reflector->getFileName()).'/../views');
        
        // add the child files view path
        $this->view->addPath(__DIR__.'/../views');
    }
}
