<?php

declare(strict_types=1);

namespace application\acl\controllers;

use Throwable;
use peels\validate\exceptions\ValidationFailed;
use orange\framework\controllers\BaseController;

class UserController extends BaseController
{
    protected array $services = [
        'UserModel' => 'model.user',
        'assets' => 'assets',
    ];

    protected function beforeMethodCalled()
    {
        $this->assets->scriptFiles(['/js/tinybind.js', '/js/tb.boot.js']);

        // defaults
        $this->data['json'] = [];
        $this->data['response'] = $this->response;
        $this->data['contentType'] = 'json';

        // success 200 OK
        $this->data['statusCode'] = 200;
    }

    // GUI - list view
    public function index(): string
    {
        return $this->view->render('people/list');
    }

    // GUI - Get Create, Edit, Delete Modals
    public function getModal(string $name): string
    {
        if (in_array($name, ['create', 'update', 'delete'])) {
            $this->data['json']['html'] = $this->view->render('people/modals/' . $name);
        } else {
            $this->data['statusCode'] = 404;
        }

        return $this->view->render('rest');
    }

    public function read(string|int $arg): string
    {
        try {
            if ($arg == 'all') {
                $this->data['json']['list'] = $this->peopleModel->getAll();
            } else {
                $this->data['json']['model'] = $this->peopleModel->getById((int)$arg);
            }
        } catch (Throwable $e) {
            $this->data['statusCode'] = 500;
        }

        return $this->view->render('rest');
    }

    public function create(): string
    {
        try {
            // success 201 Created
            $this->data['statusCode'] = 201;

            // throws ValidationFailed Exception
            $this->peopleModel->create($this->input->body());
        } catch (ValidationFailed $vf) {
            // failed 406 Not Acceptable
            $this->data['statusCode'] = $vf->getCode();
            $this->data['json']['message'] = $vf->getErrorsAsHtml('<i class="fa-solid fa-triangle-exclamation"></i> ', '', '</br>');
        }

        return $this->view->render('rest');
    }

    public function update(): string
    {
        try {
            // success 202 Accepted
            $this->data['statusCode'] = 202;

            // throws ValidationFailed Exception
            $this->peopleModel->update($this->input->body());
        } catch (ValidationFailed $vf) {
            // failed 406 Not Acceptable
            $this->data['statusCode'] = $vf->getCode();
            $this->data['json']['message'] = $vf->getErrorsAsHtml('<i class="fa-solid fa-triangle-exclamation"></i> ', '', '</br>');
        }

        return $this->view->render('rest');
    }

    public function delete(): string
    {
        try {
            // success 202 Accepted
            $this->data['statusCode'] = 202;

            // throws ValidationFailed Exception
            $this->peopleModel->delete($this->input->body());
        } catch (ValidationFailed $vf) {
            // failed 406 Not Acceptable
            $this->data['statusCode'] = $vf->getCode();
            $this->data['json']['message'] = $vf->getErrorsAsHtml('<i class="fa-solid fa-triangle-exclamation"></i> ', '', '</br>');
        }

        return $this->view->render('rest');
    }
}
