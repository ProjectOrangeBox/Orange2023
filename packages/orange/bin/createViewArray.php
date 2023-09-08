#!/usr/bin/env php
<?php

declare(strict_types=1);

define('__ROOT__', realpath(__DIR__ . '/../../../'));
chdir(__ROOT__);

require_once __ROOT__ . '/vendor/autoload.php';

mergeEnv(__ROOT__ . '/.env');

/* send config into application */
$container = cli(include __ROOT__ . '/app/config/config.php');

echo PHP_EOL;
var_dump($container->view);
echo PHP_EOL;
die();

$views = [];

foreach ($viewPaths as $path) {
    $found = globr($path . '/*.php');

    foreach ($found as $absPath) {
        $segs = explode('/views/', $absPath);

        echo $segs[1].PHP_EOL;

        $views[substr($segs[1], 0, -4)] = $absPath;
    }
}

var_export($views);

function globr($pattern, $flags = 0)
{
    $files = glob($pattern, $flags);

    foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
        $files = array_merge($files, globr($dir . '/' . basename($pattern), $flags));
    }

    return $files;
}
