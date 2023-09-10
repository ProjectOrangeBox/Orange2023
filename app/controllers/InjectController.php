<?php

declare(strict_types=1);

namespace app\controllers;

use app\controllers\BaseController;

class InjectController extends BaseController
{    
    public function index()
    {
        return '<h1>Hello</h1>';
    }
}
