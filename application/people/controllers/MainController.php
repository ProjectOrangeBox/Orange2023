<?php

declare(strict_types=1);

namespace application\people\controllers;

use application\shared\controllers\BaseController;

class MainController extends BaseController
{
    protected array $services = [
        'peopleModel' => 'model.people',
        'cache' => 'cache',
        'assets' => 'assets',
        'filter' => 'filter'
    ];

    public function init()
    {
        $this->assets->scriptFile('/js/rest_script.js');
    }

    // GUI - Gets
    public function index()
    {
        if (!$this->data['people'] = $this->cache->get('people_list')) {
            $this->data['people'] = $this->peopleModel->getAll();

            $this->cache->set('people_list', $this->data['people']);
        }

        return $this->view->render('people/list');
    }

    public function createForm()
    {
        return $this->view->render('people/create');
    }

    public function updateForm(string $recordId)
    {
        // $recordId was already "filtered" by the regular expression on the route
        // but if it wasn't we could do something like this
        // this would throw a exception on fail
        $recordId = $this->filter->input($recordId,'isRequired|isInteger');

        $this->data['record'] = $this->peopleModel->getById($recordId);

        return $this->view->render('people/edit');
    }

    public function deleteForm(string $recordId)
    {
        $this->data['record'] = $this->peopleModel->getById($recordId);

        return $this->view->render('people/delete');
    }

    public function create()
    {
        $this->process('create', '201');
    }

    public function update()
    {
        $this->process('update', '202');
    }

    public function delete()
    {
        // the posted data is filtered by the model 
        // because the model knows what the column values are suppose to be
        // but if it wasn't we could do something like this
        // this would throw a exception on fail

        var_export($this->filter->body());
        
        //$this->process('delete', '202');
    }

    protected function process(string $method, string $pass, string $fail = '406')
    {
        if (!$this->peopleModel->$method($this->request->body())) {
            container()->quickView->show($fail, ['json' => ['size' => 'large', 'title' => 'Your Form Has The Following Errors', 'message' => wrapArray($this->peopleModel->errors(), '', '</br>')]]);
        } else {
            $this->cache->delete('people_list');

            container()->quickView->show($pass);
        }
    }
}
