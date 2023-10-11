<?php

declare(strict_types=1);

namespace app\controllers;

use app\controllers\BaseController;

class FormController extends BaseController
{
    public function index()
    {
        return $this->view->render('form/index');
    }

    public function submit(){
        $this->output->flushAll()->responseCode(404)->send(true);
    }

}
