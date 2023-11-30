<?php

declare(strict_types=1);

namespace application\people\controllers;

use application\shared\controllers\BaseController;

class MainController extends BaseController
{
    protected array $models = ['people' => 'model.people'];

    // GUI - Gets
    public function index()
    {
        $this->data['people'] = cacheGetOr('people_list', $this->model->people, 'getAll', [], 1000);

        return $this->view->render('people/list');
    }

    public function createForm()
    {
        return $this->view->render('people/create');
    }

    public function updateForm(string $recordId)
    {
        $this->data['record'] = $this->model->people->getById($recordId);

        return $this->view->render('people/edit');
    }

    public function deleteForm(string $recordId)
    {
        $this->data['record'] = $this->model->people->getById($recordId);

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
        if (!$this->model->people->$method($this->request->body())) {
            container()->quickView->show($fail, ['json' => ['size' => 'large', 'title' => 'Your Form Has The Following Errors', 'message' => wrapArray($this->model->people->errors(), '', '</br>')]]);
        } else {
            cacheDelete('people_list');

            container()->quickView->show($pass);
        }
    }
}
