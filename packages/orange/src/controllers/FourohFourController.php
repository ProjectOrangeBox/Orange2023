<?php

declare(strict_types=1);

namespace orange\framework\controllers;

use orange\framework\controllers\BaseController;

class FourohfourController extends BaseController
{
    public function index()
    {
        show404();
    }
}
