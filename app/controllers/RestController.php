<?php

declare(strict_types=1);

namespace app\controllers;

use app\controllers\BaseController;

class RestController extends BaseController
{
    protected string $contentType = 'json';
    
    public function get()
    {
        $this->data->merge([
            'success' => true,
            'raw' => $this->input->raw(),
            'input' => $this->input->request(),
            'dir' => __DIR__,
            'namespace' => __NAMESPACE__,
            'class' => __CLASS__,
            'method' => __METHOD__,
        ]);

        return $this->view->render('json');
    }

    public function post()
    {
        $this->data->merge([
            'success' => true,
            'raw' => $this->input->raw(),
            'input' => $this->input->request(),
            'dir' => __DIR__,
            'namespace' => __NAMESPACE__,
            'class' => __CLASS__,
            'method' => __METHOD__,
        ]);

        return $this->view->render('json');
    }

    public function put()
    {
        $this->data->merge([
            'success' => true,
            'raw' => $this->input->raw(),
            'input' => $this->input->request(),
            'dir' => __DIR__,
            'namespace' => __NAMESPACE__,
            'class' => __CLASS__,
            'method' => __METHOD__,
        ]);

        return $this->view->render('json');
    }

    public function delete()
    {
        $this->data->merge([
            'success' => true,
            'raw' => $this->input->raw(),
            'input' => $this->input->request(),
            'dir' => __DIR__,
            'namespace' => __NAMESPACE__,
            'class' => __CLASS__,
            'method' => __METHOD__,
        ]);

        return $this->view->render('json');
    }

    public function patch()
    {
        $this->data->merge([
            'success' => true,
            'raw' => $this->input->raw(),
            'input' => $this->input->request(),
            'dir' => __DIR__,
            'namespace' => __NAMESPACE__,
            'class' => __CLASS__,
            'method' => __METHOD__,
        ]);

        return $this->view->render('json');
    }

    public function options()
    {
        $this->data->merge([
            'success' => true,
            'raw' => $this->input->raw(),
            'input' => $this->input->request(),
            'dir' => __DIR__,
            'namespace' => __NAMESPACE__,
            'class' => __CLASS__,
            'method' => __METHOD__,
        ]);

        return $this->view->render('json');
    }
}
