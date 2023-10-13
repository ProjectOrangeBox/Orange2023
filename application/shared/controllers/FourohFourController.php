<?php

declare(strict_types=1);

namespace application\shared\controllers;

use application\shared\controllers\BaseController;

class FourohfourController extends BaseController
{
    public function index()
    {
        $this->output->responseCode(404);

        return $this->view->render('404');
    }
}
