<?php

declare(strict_types=1);

namespace application\acl\controllers;

use peels\validate\exceptions\ValidationFailed;
use orange\framework\controllers\BaseController;
use Throwable;

class PermissionController extends BaseController
{
    protected array $services = [
        'acl' => 'acl',
        'assets' => 'assets',
    ];

    protected function _construct()
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
        return $this->view->render('acl/permission/list');
    }

    // GUI - Get Create, Edit, Delete Modals
    public function createForm(): string
    {
        return $this->view->render('acl/permission/create');
    }

    public function updateForm(string $id): string
    {
        // filtered by router
        $this->data['id'] = (int)$id;
        
        return $this->view->render('acl/permission/update');
    }

    public function deleteModal(): string
    {
        $this->data['json']['html'] = $this->view->render('acl/permission/delete');

        return $this->view->render('rest');
    }

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

    public function create(): string
    {
        try {
            // success 201 Created
            $this->data['statusCode'] = 201;

            // throws ValidationFailed Exception
            $this->acl->permissionModel->create($this->request->body());
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
            $this->acl->permissionModel->update($this->request->body());
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
            $this->acl->permissionModel->delete($this->request->body());
        } catch (ValidationFailed $vf) {
            // failed 406 Not Acceptable
            $this->data['statusCode'] = $vf->getCode();
            $this->data['json']['message'] = $vf->getErrorsAsHtml('<i class="fa-solid fa-triangle-exclamation"></i> ', '', '</br>');
        }

        return $this->view->render('rest');
    }
}
