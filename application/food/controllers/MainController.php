<?php

declare(strict_types=1);

namespace application\food\controllers;

use application\shared\controllers\BaseController;

class MainController extends BaseController
{
    protected array $models = ['food' => 'model.food'];

    // GUI - Gets
    public function index()
    {
        $this->data['food'] = $this->model->food->getAll();

        return $this->view->render('food/list');
    }

    public function createForm()
    {
        return $this->view->render('food/create');
    }

    public function updateForm(string $recordId)
    {
        $this->data['record'] = $this->model->food->getById($recordId);

        return $this->view->render('food/edit');
    }

    public function deleteForm(string $recordId)
    {
        $this->data['record'] = $this->model->food->getById($recordId);

        return $this->view->render('food/delete');
    }

    public function read()
    {
        $food = $this->model->food->getAll();

        container()->quickView->show('list', ['json' => $food]);
    }

    public function create()
    {
        $this->process('create', '201');
    }

    public function update()
    {
        $this->process('update', '202');
    }

    public function delete()
    {
        $this->process('delete', '202');
    }

    protected function process(string $method, string $pass, string $fail = '406')
    {
        if (!$this->model->food->$method($this->request->body())) {
            container()->quickView->show($fail, ['json' => ['size' => 'large', 'title' => 'Your Form Has The Following Errors', 'message' => wrapArray($this->model->food->errors(), '', '</br>')]]);
        } else {
            container()->quickView->show($pass);
        }
    }
}
