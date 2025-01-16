#!/usr/bin/env php
<?php

use peels\validate\Rules;

require __DIR__ . '/../../../../../vendor/autoload.php';

$testValues = [
    'empty' => '',
    'string' => 'abc',
    'integer' => '123',
    'integer100' => '100',
    'integer200' => '200',
    'hex' => 'abc123',
    'decimal' => '123.45',
    'stdClass' => ['new \StdClass()', 'raw1' => true],
    'array' => ['[]', 'raw1' => true],
    'assocArray' => ["['foo'=>'bar']", 'raw1' => true],
    'true' => ['true', 'raw1' => true],
    'false' => ['false', 'raw1' => true],
    'zero' => 0,
    'one' => 1,
    'null' => ['null', 'raw1' => true],
    'letters' => 'abcdefghijklmnopqrstuvwxyz',
    'uppercase' => 'ABCDEFG',
    'lowercase' => 'abcdefg',
    'uuid' => '50e03466-4810-11ee-be56-0242ac120002',
    'email' => 'johnny@appleseed.com',
    'emails' => 'johnny@appleseed.com,jenny@appleseed.com',
    'base64' => 'dGVzdA==',
    'ip' => '192.168.1.2',
    'url' => 'http://www.example.com',
    //'oneof' => ['a', 'a,b,c'],
];

$data = [];

$singleTemplate = file_get_contents(__DIR__ . '/support/rule.single.tmpl.php');
$fileTemplate = file_get_contents(__DIR__ . '/support/rule.body.tmpl.php');

require __DIR__ . '/../../src/Rules.php';

$reflector = new ReflectionClass(Rules::class);

$methods = $reflector->getMethods(ReflectionMethod::IS_PUBLIC);

foreach ($methods as $rec) {
    $methodName = $rec->name;

    $body = '';

    foreach ($testValues as $key => $value) {
        $value1 = $value;
        $value2 = "''";

        if (!is_array($value)) {
            $value1 = escape($value);
        } else {
            if (isset($value['raw1'])) {
                $value1 = $value[0];
            } else {
                $value1 = escape($value[0]);
            }

            if (isset($value['raw2'])) {
                $value2 = $value[1];
            } elseif (isset($value[1])) {
                $value2 = escape($value[1]);
            }
        }

        $data['basename'] = ucfirst($methodName);
        $data['lbasename'] = $methodName;
        $data['namespace'] = $methodName;
        $data['value1'] = $value1;
        $data['value2'] = $value2;
        $data['key'] = ucfirst($key);

        $body .= merge($data, $singleTemplate);
    }

    $data['body'] = $body;

    $outputFilename = 'rule' . $data['basename'] . 'Test.php';

    file_put_contents(__DIR__ . '/output/' . $outputFilename, merge($data, $fileTemplate));

    echo $outputFilename . PHP_EOL;
}

function merge(array $data, string $template): string
{
    foreach ($data as $key => $value) {
        $template = str_replace('%%' . strtoupper($key) . '%%', $value, $template);
    }

    return $template;
}

function escape($value): string
{
    if (!is_numeric($value)) {
        $value = "'" . $value . "'";
    }

    return $value;
}
