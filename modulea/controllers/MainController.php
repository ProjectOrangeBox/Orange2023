<?php

declare(strict_types=1);

namespace example\modulea\controllers;

use app\controllers\BaseController;

class MainController extends BaseController
{
    protected string $prependViewPath = __DIR__.'/../views';

    public function index()
    {
        $this->data['welcome'] = 'Welcome from '.__METHOD__;

        return $this->view->render('index');
    }
}
