<?php

declare(strict_types=1);

namespace application\people\controllers;

use peels\validate\exceptions\ValidationFailed;
use orange\framework\controllers\BaseController;
use Throwable;

class MainController extends BaseController
{
    protected array $services = [
        'peopleModel' => 'model.people',
        'cache' => 'cache',
        'assets' => 'assets',
        'filter' => 'filter'
    ];

    protected function _construct()
    {
        $this->assets->scriptFiles([
            '/js/tinybind.js',
            '/js/tb.boot.js',
            '<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>',
        ]);
        $this->assets->linkFiles([
            '<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" />',
        ]);

        // defaults
        $this->data['json'] = [];
        $this->data['contentType'] = 'json';

        // success 200 OK
        $this->data['statusCode'] = 200;
    }

    // GUI urls
    public function readList(): string
    {
        return $this->view->render('people/list');
    }

    public function readForm(string $id): string
    {
        $this->data['id'] = (int)$id;

        return $this->view->render('people/read');
    }

    public function createForm(): string
    {
        $this->data['id'] = -1;

        return $this->view->render('people/create');
    }

    public function updateForm(string $id): string
    {
        $this->data['id'] = (int)$id;

        return $this->view->render('people/update');
    }

    public function deleteForm(string $id): string
    {
        $this->data['id'] = (int)$id;

        return $this->view->render('people/delete');
    }

    // rest end points
    public function readAll(): string
    {
        try {
            $this->data['json']['records'] = $this->peopleModel->getAll();
        } catch (Throwable $e) {
            $this->data['statusCode'] = 500;
        }

        return $this->restResponse();
    }

    public function readOne(string $id): string
    {
        try {
            $this->data['json']['record'] = $this->peopleModel->getById((int)$id);
        } catch (Throwable $e) {
            $this->data['statusCode'] = 500;
        }

        return $this->restResponse();
    }

    public function create(): string
    {
        try {
            // success 201 Created
            $this->data['statusCode'] = 201;

            // throws ValidationFailed Exception
            $this->peopleModel->create($this->request->body());
        } catch (ValidationFailed $vf) {
            // failed 406 Not Acceptable
            $this->data['statusCode'] = $vf->getCode();
            $this->data['json']['message'] = $vf->getErrorsAsHtml('<i class="fa-solid fa-triangle-exclamation"></i> ', '', '</br>');
            $this->data['json']['keys'] = $vf->getKeys();
        }

        return $this->restResponse();
    }

    public function update(): string
    {
        try {
            // success 202 Accepted
            $this->data['statusCode'] = 202;

            // throws ValidationFailed Exception
            $this->peopleModel->update($this->request->body());
        } catch (ValidationFailed $vf) {
            // failed 406 Not Acceptable
            $this->data['statusCode'] = $vf->getCode();
            $this->data['json']['message'] = $vf->getErrorsAsHtml('<i class="fa-solid fa-triangle-exclamation"></i> ', '', '</br>');
            $this->data['json']['keys'] = $vf->getKeys();
        }

        return $this->restResponse();
    }

    public function delete(): string
    {
        try {
            // success 202 Accepted
            $this->data['statusCode'] = 202;

            // throws ValidationFailed Exception
            $this->peopleModel->delete($this->request->body());
        } catch (ValidationFailed $vf) {
            // failed 406 Not Acceptable
            $this->data['statusCode'] = $vf->getCode();
            $this->data['json']['message'] = $vf->getErrorsAsHtml('<i class="fa-solid fa-triangle-exclamation"></i> ', '', '</br>');
            $this->data['json']['keys'] = $vf->getKeys();
        }

        return $this->restResponse();
    }
}
