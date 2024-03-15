<?php

declare(strict_types=1);

namespace application\welcome\controllers;

use peels\disc\Disc;
use orange\framework\controllers\BaseController;

class TestController extends BaseController
{
    public function index()
    {
        $hb = container()->get('handlebars');

        $data = [
            'page_title'=>'FOOBAR',
        ];

        $x = $hb->render('basic',$data);

        return $x;
    }

    public function user()
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
    }

    public function uploadForm()
    {
        return $this->view->render();
    }

    public function uploadProcess()
    {
        echo '<pre>';
        Disc::root(__ROOT__);
        $fs = Disc::uploads($this->request->files(), '/var/uploadsTemp');

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
    }
}
