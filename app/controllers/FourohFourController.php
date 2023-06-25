<?php

declare(strict_types=1);

namespace app\controllers;

class FourohfourController extends BaseController
{
    public function index()
    {
        $this->output->responseCode(404);

        return $this->view->render('404');
    }
}
