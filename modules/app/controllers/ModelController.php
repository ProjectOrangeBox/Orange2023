<?php

declare(strict_types=1);

namespace app\controllers;

use app\controllers\BaseController;

class ModelController extends BaseController
{
    public function index()
    {
        $personModel = container()->get('model.person');

        // returns personModelRow class for each row
        $this->data['person'] = $personModel->getByName('Johnny Appleseed');

        $this->data['persons'] = $personModel->getAll();

        return $this->view->render('model');
    }
}
