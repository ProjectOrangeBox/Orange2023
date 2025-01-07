<?php

use peels\collector\Collector;

require __DIR__ . '/src/CollectorInterface.php';
require __DIR__ . '/src/CollectorException.php';
require __DIR__ . '/src/Collector.php';

$collector = Collector::getInstance('cookies');

$collector->add('foo', 'error 1');
$collector->add('foo', 'error 2');

$collector->add('bar', 'error 1');

$collector->add('error 1');

echo $collector->asJson(JSON_PRETTY_PRINT)->collect('bar');
echo $collector->asJson(JSON_PRETTY_PRINT)->collect('*');
echo $collector->asHtml('<br>', '<div class="head">', '</div>', '<div class="line key_{{key}}">', '</div>')->collectAll();

$collector = Collector::getInstance();

$collector->add('foo');
$collector->add('bar');
$collector->add('123');

echo $collector->asJson(JSON_PRETTY_PRINT)->collect();

$collector = Collector::getInstance();

$collector->add(['foo', 'bar', '123']);

echo $collector->asJson(JSON_PRETTY_PRINT)->collect();

$collector = Collector::getInstance();

$collector->add('people', ['foo', 'bar', '123']);

echo $collector->asJson(JSON_PRETTY_PRINT)->collect();

$collector = Collector::getInstance('cookies');

echo $collector->asHtml('<br>', '<div class="invoice">', '</div>', '<div class="lineitem index_{{ key }}">', '</div>')->collectAll();
