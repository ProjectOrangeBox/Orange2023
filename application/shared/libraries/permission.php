<?php

declare(strict_types=1);

namespace application\shared\libraries;

use orange\framework\interfaces\InputInterface;
use orange\framework\interfaces\RouterInterface;

class permission
{
    public function beforeController(RouterInterface $routerService, InputInterface $input): bool
    {
        // do something here
        //show401();

        return true; // continue
    }
}
