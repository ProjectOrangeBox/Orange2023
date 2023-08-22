#!/usr/bin/env php
<?php

declare(strict_types=1);

ini_set("memory_limit", "999M");
ini_set('display_errors', '1');
error_reporting(E_ALL ^ E_NOTICE);

define('__ROOT__', realpath(__DIR__));

chdir(__ROOT__);

$argc = $_SERVER['argc'];
$argv = $_SERVER['argv'];
$n = chr(10);

if ($argc != 2) {
    die('Please provide file to create output for.');
}

$file = realpath($argv[1]);

if (!$file) {
    die('Could not locate "' . $argv[1] . '".');
}

echo $file . $n;

$basename = basename($file, '.php');
$template = file_get_contents(__ROOT__ . '/support/unitTestTemplate.php');

$methodsPHP = [];

foreach (['public', 'protected'] as $type) {
    $methodText = '';
    $singleTemplate = file_get_contents(__ROOT__ . '/support/unitTest' . ucfirst($type) . 'Template.php');
    $methods = getMethods($type, $file);

    foreach ($methods as $method) {
        echo $method . PHP_EOL;
        $methodText .= str_replace('{{method}}', ucfirst($method), $singleTemplate);
    }

    $methodsPHP[$type] = $methodText;
}

$complete = '<?php' . PHP_EOL . PHP_EOL . str_replace(['{{classname}}', '{{public}}','{{protected}}'], [$basename, $methodsPHP['public'],$methodsPHP['protected']], $template) . PHP_EOL;

$finalName = ucfirst($basename) . 'Test.php';

if (file_exists($finalName)) {
    unlink($finalName);
}

file_put_contents($finalName, $complete);

function getMethods(string $type, string $file): array
{
    $methods = [];
    $re = '/' . $type . '[\s*]function[\s*](.*)\(/m';
    $string = file_get_contents($file);

    preg_match_all($re, $string, $matches, PREG_SET_ORDER, 0);


    foreach ($matches as $match) {
        $methods[] = $match[1];
    }

    return $methods;
}
