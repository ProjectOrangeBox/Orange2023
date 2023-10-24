<?php

declare(strict_types=1);

namespace application\people\controllers;

use application\shared\controllers\BaseController;

class MainController extends BaseController
{
    protected array $models = ['parent' => 'model.parent'];
    protected array $libraries = ['helpers'];

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
        if (!$this->model->parent->$method($this->input->request())) {
            container()->quickView->load($fail, ['errors' => $this->model->parent->errors()]);
        } else {
            container()->quickView->load($pass);
        }
    }
}
