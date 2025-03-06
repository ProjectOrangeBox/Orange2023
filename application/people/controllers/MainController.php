<?php

declare(strict_types=1);

namespace application\people\controllers;

use orange\framework\traits\ConfigurationTrait;
use peels\validate\exceptions\ValidationFailed;
use application\shared\controllers\CrudController;

class MainController extends CrudController
{
    use ConfigurationTrait;

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
        $this->assets->scriptFiles([]);

        $this->assets->linkFiles([]);
    }

    # [route(get,/colordropdown,peoplecolordropdown)]
    public function colordropdown(): string
    {
        return $this->preformCRUD('colorModel', 'getAll');
    }

    // GUI urls
    # [route(get,/people,peopleReadList)]
    public function readList(): string
    {
        return $this->view->render($this->viewDirectory . 'list');
    }

    # [route(get, /people/show/(\d+), peopleReadForm)]
    public function readForm(string $id): string
    {
        return $this->view->render($this->viewDirectory . 'read', ['id' => (int)$id]);
    }

    # [route(get, /people/create, peopleCreateForm)]
    public function createForm(): string
    {
        return $this->view->render($this->viewDirectory . 'create', ['id' => -1]);
    }

    # [route(get, /people/update/(\d+), peopleUpdateForm)]
    public function updateForm(string $id): string
    {
        return $this->view->render($this->viewDirectory . 'modals/update', ['id' => (int)$id]);
    }

    # [route(get, /people/delete/(\d+), peopleDeleteForm)]
    public function deleteForm(string $id): string
    {
        return $this->view->render($this->viewDirectory . 'modals/delete', ['id' => (int)$id]);
    }

    // rest end points
    # [route(get, /people/all, peopleReadAll)]
    public function readAll(): string
    {
        return $this->preformCRUD($this->defaultModel, 'getAll');
    }

    # [route(get, /people/(\d+), peopleReadOne)]
    public function readOne(string $id): string
    {
        return $this->preformCRUD($this->defaultModel, 'getById', [(int)$id]);
    }

    # [route(get, /people/new, peopleReadNew)]
    public function readNew(): string
    {
        return $this->preformCRUD($this->defaultModel, 'getNew');
    }

    # [route(post, /people, peopleCreate)]
    public function create(): string
    {
        try {
            $response = $this->preformCRUD($this->defaultModel, 'create', [$this->input->body()]);
        } catch (ValidationFailed $vf) {
            $response = $this->rest406($vf);
        }

        return $response;
    }

    # [route(put, /people/(\d+), peopleUpdate)]
    public function update(): string
    {
        try {
            $response = $this->preformCRUD($this->defaultModel, 'update', [$this->input->body()]);
        } catch (ValidationFailed $vf) {
            $response = $this->rest406($vf);
        }

        return $response;
    }

    # [route(delete, /people/(\d+), peopleDelete)]
    public function delete(): string
    {
        try {
            $response = $this->preformCRUD($this->defaultModel, 'delete', [$this->input->body()]);
        } catch (ValidationFailed $vf) {
            $response = $this->rest406($vf);
        }

        return $response;
    }
}
