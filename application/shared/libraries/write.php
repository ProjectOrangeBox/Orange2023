<?php

declare(strict_types=1);

namespace application\shared\libraries;

use orange\framework\interfaces\InputInterface;
use orange\framework\interfaces\OutputInterface;
use orange\framework\interfaces\RouterInterface;

class write
{
    public function afterController(RouterInterface $router, InputInterface $input, OutputInterface $output): bool
    {
        //file_put_contents(__ROOT__ . '/var/logs/test.txt', date('Y-m-d H:i:s') . $output->get(), FILE_APPEND | LOCK_EX);

        //$output->write(date('Y-M-d H:i:s'));

        return true; // continue
    }
}
