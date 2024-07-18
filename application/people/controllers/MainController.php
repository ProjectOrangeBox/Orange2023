<?php

declare(strict_types=1);

namespace application\people\controllers;

use orange\framework\controllers\BaseController;

class MainController extends BaseController
{
    protected array $services = [
        'peopleModel' => 'model.people',
        'cache',
        'assets',
        'filter',
        'fig',
    ];

    protected function beforeMethodCalled()
    {
        $this->assets->scriptFiles([
            '/js/tinybind.js',
            '/js/app.js',
            //'<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>',
        ]);
        $this->assets->linkFiles([
            //'<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" />',
        ]);
    }

    // GUI urls
    # [route(get,/people,people_get_read_list)]
    public function readList(): string
    {
        return $this->view->render('people/list');
    }

    # [route(get, /people/show/(\d+), people_get_read_form)]
    public function readForm(string $id): string
    {
        return $this->view->render('people/read', ['id' => (int)$id]);
    }

    # [route(get, /people/create, people_get_create_form)]
    public function createForm(): string
    {
        return $this->view->render('people/create', ['id' => -1]);
    }

    # [route(get, /people/update/(\d+), people_update)]
    public function updateForm(string $id): string
    {
        return $this->view->render('people/update', ['id' => (int)$id]);
    }

    # [route(get, /people/delete/(\d+), people_delete)]
    public function deleteForm(string $id): string
    {
        return $this->view->render('people/delete', ['id' => (int)$id]);
    }

    // rest end points
    # [route(get, /people/all, people_all)]
    public function readAll(): string
    {
        return $this->preformCRUD('peopleModel', 'getAll');
    }

    # [route(get, /people/(\d+), people_one)]
    public function readOne(string $id): string
    {
        return $this->preformCRUD('peopleModel', 'getById', [(int)$id]);
    }

    # [route(post, /people, people_post)]
    public function create(): string
    {
        return $this->preformCRUD('peopleModel', 'create', [$this->request->body()]);
    }

    # [route(put, /people/(\d+), people_put)]
    public function update(): string
    {
        return $this->preformCRUD('peopleModel', 'update', [$this->request->body()]);
    }

    # [route(delete, /people/(\d+), people_delete)]
    public function delete(): string
    {
        return $this->preformCRUD('peopleModel', 'delete', [$this->request->body()]);
    }
}
