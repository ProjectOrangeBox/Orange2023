<?php

declare(strict_types=1);

namespace app\controllers;

use app\controllers\BaseController;

class ModelController extends BaseController
{
    public function index()
    {
        $personModel = container()->get('model.person');

        $this->data['person'] = $personModel->getByName('Johnny Appleseed');

        return $this->view->render('model');
    }
}
