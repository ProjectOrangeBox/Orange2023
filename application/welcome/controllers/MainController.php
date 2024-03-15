<?php

declare(strict_types=1);

namespace application\welcome\controllers;

use orange\framework\controllers\BaseController;

class MainController extends BaseController
{
    public function index()
    {
        $this->data->merge([
            'address' => '123 South Main Street<br />Somewhere, AZ 12345',
            'about' => 'About Bottle Washing',
            'position' => $this->config->get('user', 'position'),
            'h1' => 'Hello World!',
            'cash' => '19.95',
        ]);

        $this->data['around'] = 'AROUND THE WEB';
        $this->data['name'] = 'Johnny Appleseed';

        return $this->view->render();
    }

    public function missing()
    {
        $this->output->flushAll()->responseCode(404)->send(true);
    }

    public function redirect()
    {
        $this->output->redirect('/');
    }
}
