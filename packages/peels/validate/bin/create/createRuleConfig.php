#!/usr/bin/env php
<?php

require __DIR__ . '/../../../../../vendor/autoload.php';

$namePrefix = [
    'Cast' => 'cast',
    'Filters' => 'to',
];

$array = [];

foreach (glob(__DIR__ . '/../../src/rules/*.php') as $file) {
    if (substr($file, -12) != 'Abstract.php') {
        $basename = basename($file, '.php');

        $prefix = $namePrefix[$basename] ?? '';

        processClass($array, $basename, $prefix);
    }
}

echo PHP_EOL;

foreach ($array as $file => $records) {
    echo PHP_EOL;
    echo '// --' . $file . PHP_EOL;

    ksort($records);

    foreach ($records as $record) {
        echo $record;
    }
}

echo PHP_EOL;

exit;

function processClass(array &$array, string $class, string $prefix = '')
{
    echo $class . PHP_EOL;

    $namespace = '\\peels\\validate\\rules\\' . $class;

    $reflector = new ReflectionClass($namespace);

    $methods = $reflector->getMethods(ReflectionMethod::IS_PUBLIC);

    foreach ($methods as $rec) {
        $methodName = $rec->name;

        if (!in_array($methodName, ['__construct'])) {
            //echo $methodName.PHP_EOL;

            /*
            'castString' => \peels\validate\rules\Cast::class.'::string',
            */

            $name = (!empty($prefix)) ? $prefix . ucfirst($methodName) : $methodName;

            $array[$class][$name] = "'" . $name . "' => " . $namespace . "::class.'::" . $methodName . "'," . PHP_EOL;
        }
    }
}
