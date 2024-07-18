<?php

declare(strict_types=1);

namespace application\welcome\controllers;

use orange\framework\controllers\BaseController;

class MainController extends BaseController
{
    protected array $services = [
        'language',
        'fig',
    ];

    public function index(): string
    {
        $this->data->merge([
            'address' => '123 South Main Street<br />Somewhere, AZ 12345',
            'about' => $this->language->line('main.about', 'Default Msg'),
            'aboutText' => $this->language->line('main.about text'),
            'position' => $this->config->get('app', 'position'),
            'h1' => $this->config->get('app', 'h1'),
            'file' => $this->config->get('app', 'file'),
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
