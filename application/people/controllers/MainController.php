<?php

declare(strict_types=1);

namespace application\people\controllers;

use application\shared\controllers\CrudController;

class MainController extends CrudController
{
    // view directory
    protected $viewDirectory = 'people/';

    // what is the default model
    protected $defaultModel = 'peopleModel';

    protected array $services = [
        'peopleModel' => 'model.people',
        'colorModel' => 'model.color',
        'cache',
        'assets',
        'filter',
        'fig',
        'validate',
    ];

    protected function beforeMethodCalled()
    {
        $this->assets->scriptFiles([
            '/js/tinybind.js',
            '/js/sprintf.min.js',
            '/js/app.js',
            '/js/orangeBind/orangeFormatters.js',
            //'<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>',
        ]);
        $this->assets->linkFiles([
            //'<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" />',
        ]);
    }

    # [route(get,/colordropdown,colordropdown)]
    public function colordropdown(): string
    {
        return $this->preformCRUD('colorModel', 'getAll', [], 'colors');
    }

    # [route(get,/peopledropdown,peopledropdown)]
    public function dropdown(): string
    {
        $this->data['statusCode'] = 200;
        $this->data['contentType'] = 'json';

        $this->data['json']['dropdown'] = [
            'selected' => 'two',
            'friends' => [
                ['name' => 'one'],
                ['name' => 'two'],
                ['name' => 'three'],
            ],
        ];

        return $this->restResponse();
    }

    # [route(get,/peopledropdown2,peopledropdown2)]
    public function dropdown2(): string
    {
        $this->data['statusCode'] = 200;
        $this->data['contentType'] = 'json';

        $this->data['json']['dropdown2'] = [
            'selected' => 'two',
        ];

        return $this->restResponse();
    }

    // GUI urls
    # [route(get,/people,people_get_read_list)]
    public function readList(): string
    {
        return $this->view->render($this->viewDirectory . 'list');
    }

    # [route(get, /people/show/(\d+), people_get_read_form)]
    public function readForm(string $id): string
    {
        return $this->view->render($this->viewDirectory . 'read', ['id' => (int)$id]);
    }

    # [route(get, /people/create, people_get_create_form)]
    public function createForm(): string
    {
        return $this->view->render($this->viewDirectory . 'create', ['id' => -1]);
    }

    # [route(get, /people/update/(\d+), people_update)]
    public function updateForm(string $id): string
    {
        return $this->view->render($this->viewDirectory . 'update', ['id' => (int)$id]);
    }

    # [route(get, /people/delete/(\d+), people_delete)]
    public function deleteForm(string $id): string
    {
        return $this->view->render($this->viewDirectory . 'delete', ['id' => (int)$id]);
    }

    // rest end points
    # [route(get, /people/all, people_all)]
    public function readAll(): string
    {
        return $this->preformCRUD($this->defaultModel, 'getAll');
    }

    # [route(get, /people/(\d+), people_one)]
    public function readOne(string $id): string
    {
        return $this->preformCRUD($this->defaultModel, 'getById', [(int)$id]);
    }

    public function readNew(): string
    {
        return $this->preformCRUD($this->defaultModel, 'getNew');
    }

    # [route(post, /people, people_post)]
    public function create(): string
    {
        return $this->preformCRUD($this->defaultModel, 'create', [$this->input->body()]);
    }

    # [route(put, /people/(\d+), people_put)]
    public function update(): string
    {
        return $this->preformCRUD($this->defaultModel, 'update', [$this->input->body()]);
    }

    # [route(delete, /people/(\d+), people_delete)]
    public function delete(): string
    {
        return $this->preformCRUD($this->defaultModel, 'delete', [$this->input->body()]);
    }
}
