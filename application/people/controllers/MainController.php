<?php

declare(strict_types=1);

namespace application\people\controllers;

use application\shared\controllers\BaseController;

class MainController extends BaseController
{
    protected array $models = ['parent' => 'model.parent'];

    // GUI - Gets
    public function index()
    {
        $this->data['parents'] = $this->model->parent->getAll();

        return $this->view->render('people/list');
    }

    public function createForm()
    {
        return $this->view->render('people/create');
    }

    public function updateForm(string $recordId)
    {
        $this->data['record'] = $this->model->parent->getById($recordId);

        return $this->view->render('people/edit');
    }

    public function deleteForm(string $recordId)
    {
        $this->data['record'] = $this->model->parent->getById($recordId);

        return $this->view->render('people/delete');
    }

    // actions - posted
    public function create()
    {
        //$this->output->responseCode(201);
        $validate = container()->validate;
        $error = container()->error;

        $validate->addError('Oh this is Bad!');
        $validate->addError('Oh this is also Bad!');

        $error->collectErrors($validate, 'errors');

        // show error and exit
        $error->responseCode(406)->onErrorsShow();

        // else send 201
        $this->output->responseCode(201);
    }

    public function update(string $recordId)
    {
    }

    public function delete(string $recordId)
    {
    }
}
