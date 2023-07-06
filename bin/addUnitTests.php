#!/usr/bin/env php
<?php

define('__ROOT__', realpath(__DIR__ . '/../packages/orange'));

$interfacePath = realpath(__ROOT__ . '/src/interfaces');

$skipMethods = [
    '__debugInfo',
];

foreach (glob($interfacePath . '/*.php') as $file) {
    require_once $file;

    $basename = basename($file, '.php');
    $className = substr($basename, 0, -9);

    echo $basename . PHP_EOL;

    $class = new ReflectionClass('\dmyers\orange\interfaces\\' . $basename);

    $methods = '';

    foreach ($class->getMethods() as $methodRecord) {
        if (!in_array($methodRecord->name,$skipMethods)) {
            echo $methodRecord->name . PHP_EOL;
            
            $methods .= view('unitTestSingleTemplate', ['method' => ucfirst($methodRecord->name)]) . PHP_EOL;
        }
    }

    $completePHP = '<?php' . PHP_EOL . PHP_EOL . trim(view('unitTestTemplate', ['methodsText' => $methods, 'className' => $className])) . PHP_EOL;

    $completeFile = __ROOT__ . '/unitTests/' . $className . 'Test.php';

    if (file_exists($completeFile)) {
        echo '*** ' . $completeFile . ' already exists' . PHP_EOL;
    } else {
        file_put_contents($completeFile, $completePHP);
    }
}


function view(string $_name, array $_data = []): string
{
    extract($_data, EXTR_OVERWRITE);

    ob_start();

    $_viewFile = __DIR__ . '/support/' . $_name . '.php';

    if (!file_exists($_viewFile)) {
        throw new Exception('view file "' . $_viewFile . '" not found.');
    }

    require $_viewFile;

    return ob_get_clean();
}
