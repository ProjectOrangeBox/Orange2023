<?php

declare(strict_types=1);

namespace application\welcome\controllers;

use peels\disc\Disc;
use orange\framework\controllers\BaseController;

class TestController extends BaseController
{
    protected array $services = [
        'disc',
    ];

    public function index(): string
    {
        $mv = container()->get('mergeView');

        return $mv->renderString('hello world! I hear your name is {{ yourname }}.', ['yourname' => 'Johnny']);
    }

    public function user(): string
    {
        $user = container()->user;

        var_dump($user->email);
        var_dump($user->username);
        var_dump($user->loggedIn);
        var_dump($user->isAdmin);
        var_dump($user->isGuest);
        var_dump($user->can('foobar'));
        var_dump($user->dashboard_url);
        var_dump($user->ext);

        $user->ext = mt_rand(100, 999);

        $user->update();

        return '';
    }

    public function uploadForm(): string
    {
        return $this->view->render();
    }

    public function uploadProcess(): string
    {
        echo '<pre>';
        $fs = Disc::uploads($this->input->files(), '/var/uploadsTemp');

        foreach ($fs as $f) {
            echo '---------------' . PHP_EOL;
            if (!isset($f->hasError)) {
                $f = $f->move('/htdocs/assets/img');

                var_dump($f->size());
                var_dump($f->getType());
                var_dump($f->isFile());
                var_dump($f->getPath());
                var_dump($f->getExtension());
                var_dump($f->mime());
                var_dump($f->width());
                var_dump(Disc::formatPermission($f->getPerms()));

                echo e($f->src(true)) . PHP_EOL;
            } else {
                echo 'ERROR:' . PHP_EOL;
                echo $f->error . PHP_EOL;
                echo $f->errorMsg . PHP_EOL;
            }
        }

        return '';
    }
}
