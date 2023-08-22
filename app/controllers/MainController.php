<?php

declare(strict_types=1);

namespace app\controllers;

use dmyers\orange\Container;
use app\controllers\BaseController;

class MainController extends BaseController
{
    public function index()
    {
        $this->data->merge([
            'address' => '123 South Main Street<br />Somewhere, AZ 12345',
            'about' => 'About Bottle Washing',
            'position' => 'Head Bottle Washer',
            'h1' => 'Hello World!',
        ]);

        $this->data['around'] = 'AROUND THE WEB';
        $this->data['name'] = 'Johnny Appleseed';

        return $this->view->render('index');
    }

    public function missing(){
        $this->output->flushAll()->responseCode(404)->send(true);
    }

    public function redirect(){
        $this->output->redirect('/');
    }

    public function error(){
        $this->output->flushAll()->responseCode(500)->send(true);
    }
}
