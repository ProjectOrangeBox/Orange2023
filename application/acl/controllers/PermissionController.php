<?php

declare(strict_types=1);

namespace application\acl\controllers;

use Throwable;
use peels\validate\exceptions\ValidationFailed;
use orange\framework\controllers\BaseController;

class PermissionController extends BaseController
{
    protected array $services = [
        'acl',
        'assets',
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
    # [route(get, /acl/permission)]
    public function index(): string
    {
        return $this->view->render('acl/permission/list');
    }

    # [route(get, /acl/permission/create)]
    public function createForm(): string
    {
        return $this->view->render('acl/permission/create');
    }

    # [route(get, acl/permission/update/(\d+))]
    public function updateForm(string $id): string
    {
        // filtered by router
        $this->data['id'] = (int)$id;

        return $this->view->render('acl/permission/update');
    }

    # [route(get, /acl/permission/delete)]
    public function deleteModal(): string
    {
        $this->data['json']['html'] = $this->view->render('acl/permission/delete');

        return $this->view->render('rest');
    }

    # [route(get, /acl/permission/([a-z|\d+]+))]
    public function read(string $arg): string
    {
        try {
            if ($arg == 'all') {
                $this->data['json']['records'] = $this->acl->permissionModel->readAll();
            } else {
                $this->data['json']['record'] = $this->acl->permissionModel->read((int)$arg);
            }
        } catch (Throwable $e) {
            $this->data['statusCode'] = 500;
        }

        return $this->view->render('rest');
    }

    # [route(post, /acl/permission)]
    public function create(): string
    {
        try {
            // success 201 Created
            $this->data['statusCode'] = 201;

            // throws ValidationFailed Exception
            $this->acl->permissionModel->create($this->input->body());
        } catch (ValidationFailed $vf) {
            // failed 406 Not Acceptable
            $this->data['statusCode'] = $vf->getCode();
            $this->data['json']['message'] = $vf->getErrorsAsHtml('<i class="fa-solid fa-triangle-exclamation"></i> ', '', '</br>');
        }

        return $this->view->render('rest');
    }

    # [route(put, /acl/permission/(\d+))]
    public function update(): string
    {
        try {
            // success 202 Accepted
            $this->data['statusCode'] = 202;

            // throws ValidationFailed Exception
            $this->acl->permissionModel->update($this->input->body());
        } catch (ValidationFailed $vf) {
            // failed 406 Not Acceptable
            $this->data['statusCode'] = $vf->getCode();
            $this->data['json']['message'] = $vf->getErrorsAsHtml('<i class="fa-solid fa-triangle-exclamation"></i> ', '', '</br>');
        }

        return $this->view->render('rest');
    }

    # [route(delete, /acl/permission/(\d+))]
    public function delete(): string
    {
        try {
            // success 202 Accepted
            $this->data['statusCode'] = 202;

            // throws ValidationFailed Exception
            $this->acl->permissionModel->delete($this->input->body());
        } catch (ValidationFailed $vf) {
            // failed 406 Not Acceptable
            $this->data['statusCode'] = $vf->getCode();
            $this->data['json']['message'] = $vf->getErrorsAsHtml('<i class="fa-solid fa-triangle-exclamation"></i> ', '', '</br>');
        }

        return $this->view->render('rest');
    }
}
