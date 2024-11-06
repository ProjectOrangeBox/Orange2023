#!/usr/bin/env php
<?php

declare(strict_types=1);

define('__ROOT__', realpath(__DIR__ . '/../'));

$_ENV = array_replace_recursive($_ENV, parse_ini_file(__ROOT__ . '/.env', true, INI_SCANNER_TYPED));

$config = include __ROOT__ . '/config/config.php';

// load any bootstrap file here

require_once __ROOT__ . '/vendor/autoload.php';

/* send config into application */
$container = \orange\framework\Application::cli($config);

$console = $container->console;

$console->line();

$console->primary('This is a primary test');
$console->secondary('This is a secondary test');

$console->line();

$console->error('Danger, Will Robinson!');
$console->success('Task Success!');
$console->info('Important Information!');
$console->warning('Warning! System Overload!');

$console->line();

$console->minimumArguments(1, 'Please provide the filename.');

$filename = $console->getArgument(1);

$console->echo('Using File <bold>' . $filename);

$color = $console->getArgumentByOption('-color');

$console->echo('Using Color <bold>' . $color);

$last = $console->getLastArgument();

$console->echo('Last <bold>' . $last);

$arg1 = $console->getArgument(1);

$console->echo('Arg 1 <bold>' . $arg1);

$table = [
    ['Colors', 'Names', 'Age'],
    ['Red', 'Johnny Apple', 23],
    ['Purple', 'Jenny Smith', 23],
    ['Yellow', 'Jake Louder', 23],
    ['Yellow', 'Jack Black', 857],
];

$console->table($table);

$name = $console->getLine('What is your name?');

$console->echo('<bright blue>Hello <magenta>' . $name);

$console->list([1 => 'red', 2 => 'blue', 3 => 'green']);

$selection = $console->getOneOf(null, [1, 2, 3]);

$console->primary('You selected <magenta>' . $selection);
