<?php

declare(strict_types=1);

namespace application\child\controllers;

use orange\framework\controllers\BaseController;

class MainController extends BaseController
{
    // view directory
    protected $viewDirectory = 'child/';

    // what is the default model
    protected $defaultModel = 'childModel';

    // auto load these services
    protected array $services = [
        'childModel' => 'model.child',
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
        $this->data['view'] = 'child';
    }

    // GUI urls
    # [route(get,/child,child_get_read_list)]
    public function readList(): string
    {
        return $this->view->render($this->viewDirectory . 'list');
    }

    # [route(get, /child/show/(\d+), child_get_read_form)]
    public function readForm(string $id): string
    {
        return $this->view->render($this->viewDirectory . 'read', ['id' => (int)$id]);
    }

    # [route(get, /child/create, child_get_create_form)]
    public function createForm(): string
    {
        return $this->view->render($this->viewDirectory . 'create', ['id' => -1]);
    }

    # [route(get, /child/update/(\d+), child_update)]
    public function updateForm(string $id): string
    {
        return $this->view->render($this->viewDirectory . 'update', ['id' => (int)$id]);
    }

    # [route(get, /child/delete/(\d+), child_delete)]
    public function deleteForm(string $id): string
    {
        return $this->view->render($this->viewDirectory . 'delete', ['id' => (int)$id]);
    }

    // rest end points

    # [route(get, /child/all, child_all)]
    public function readAll(): string
    {
        return $this->preformCRUD($this->defaultModel, 'getAllJoined');
    }

    # [route(get, /child/(\d+), child_one)]
    public function readOne(string $id): string
    {
        return $this->preformCRUD($this->defaultModel, 'getById', [(int)$id]);
    }

    # [route(post, /child, child_post)]
    public function create(): string
    {
        return $this->preformCRUD($this->defaultModel, 'create', [$this->input->body()]);
    }

    # [route(put, /child/(\d+), child_put)]
    public function update(): string
    {
        return $this->preformCRUD($this->defaultModel, 'update', [$this->input->body()]);
    }

    # [route(delete, /child/(\d+), child_delete)]
    public function delete(): string
    {
        return $this->preformCRUD($this->defaultModel, 'delete', [$this->input->body()]);
    }
}
