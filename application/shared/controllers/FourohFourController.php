<?php

declare(strict_types=1);

namespace application\shared\controllers;

use application\shared\controllers\BaseController;

class FourohfourController extends BaseController
{
    protected $services = ['error'];
    
    public function index()
    {
        $this->error->show404();
    }
}
