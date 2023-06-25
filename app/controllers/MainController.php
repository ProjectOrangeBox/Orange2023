<?php

declare(strict_types=1);

namespace app\controllers;

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
}
