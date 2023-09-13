#!/usr/bin/env php
<?php

declare(strict_types=1);

define('__ROOT__', realpath(__DIR__ . '/../../../'));
chdir(__ROOT__);

$argc = $_SERVER['argc'];
$argv = $_SERVER['argv'];

echo PHP_EOL;

if ($argc != 2) {
    die('Please provide view folder relative to ' . __ROOT__ . PHP_EOL . PHP_EOL);
}

$path = __ROOT__ . '/' . trim($argv[1]);

if (!is_dir($path)) {
    die('Can not locate ' . $path . PHP_EOL);
}

$found = globr($path . '/*.php');

foreach ($found as $absPath) {
    $name = substr($absPath, strlen($path) + 1);
    $key = substr($name, 0, -4);

    $views[$key] = $absPath;
}

echo '$views = ' . var_export($views, true) . ';' . PHP_EOL . PHP_EOL;

/* functions */

function globr($pattern, $flags = 0)
{
    $files = glob($pattern, $flags);

    foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
        $files = array_merge($files, globr($dir . '/' . basename($pattern), $flags));
    }

    return $files;
}
