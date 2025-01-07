<?php

declare(strict_types=1);

namespace application\join\controllers;

use orange\framework\controllers\BaseController;

class MainController extends BaseController
{
    // view directory
    protected $viewDirectory = 'join/';

    // what is the default model
    protected $defaultModel = 'joinModel';

    // auto load these services
    protected array $services = [
        'joinModel' => 'model.join',
        'cache',
        'assets',
        'filter',
        'fig',
    ];

    // before any other methods are called this is called
    protected function beforeMethodCalled()
    {
        // add these to the view
        $this->assets->scriptFiles([
            '/js/tinybind.js',
            '/js/app.js',
        ]);
        
        // add these to the view
        $this->assets->linkFiles([]);
        
        // setup the view property
        $this->data['view'] = 'join';
    }

    // GUI urls
    # [route(get,/join,join_get_read_list)]
    public function readList(): string
    {
        return $this->view->render($this->viewDirectory . 'list');
    }

    # [route(get, /join/show/(\D+), join_get_read_form)]
    public function readForm(string $id): string
    {
        return $this->view->render($this->viewDirectory . 'read', ['id' => (int)$id]);
    }

    # [route(get, /join/create, join_get_create_form)]
    public function createForm(): string
    {
        return $this->view->render($this->viewDirectory . 'create', ['id' => -1]);
    }

    # [route(get, /join/update/(\d+), join_update)]
    public function updateForm(string $id): string
    {
        return $this->view->render($this->viewDirectory . 'update', ['id' => (int)$id]);
    }

    # [route(get, /join/delete/(\d+), join_delete)]
    public function deleteForm(string $id): string
    {
        return $this->view->render($this->viewDirectory . 'delete', ['id' => (int)$id]);
    }

    // rest end points
    # [route(get, /join/all, join_all)]
    public function readAll(): string
    {
        return $this->preformCRUD($this->defaultModel, 'getAll');
    }

    # [route(get, /join/(\d+), join_one)]
    public function readOne(string $id): string
    {
        return $this->preformCRUD($this->defaultModel, 'getById', [(int)$id]);
    }

    # [route(post, /join, join_post)]
    public function create(): string
    {
        return $this->preformCRUD($this->defaultModel, 'create', [$this->input->body()]);
    }

    # [route(put, /join/(\d+), join_put)]
    public function update(): string
    {
        return $this->preformCRUD($this->defaultModel, 'update', [$this->input->body()]);
    }

    # [route(delete, /join/(\d+), join_delete)]
    public function delete(): string
    {
        return $this->preformCRUD($this->defaultModel, 'delete', [$this->input->body()]);
    }
}
