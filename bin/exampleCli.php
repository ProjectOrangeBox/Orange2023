#!/usr/bin/env php
<?php

declare(strict_types=1);

define('__ROOT__', realpath(__DIR__ . '/../'));
chdir(__ROOT__);

require_once __ROOT__ . '/vendor/autoload.php';

mergeEnv(__ROOT__ . '/.env');

/* send config into application */
$container = cli(include __ROOT__ . '/app/config/config.php');

$console = $container->console;

$console->line();

$console->echo('<primary>This is a primary test');
$console->echo('<secondary>This is a secondary test');

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
    ['Colors','Names','Age'],
    ['Red','Johnny Apple',23],
    ['Purple','Jenny Smith',23],
    ['Yellow','Jake Louder',23],
    ['Yellow','Jack Black',857],
];

$console->table($table);

$name = $console->getLine('What is your name?');

$console->echo('<bright blue>Hello <secondary>' . $name);

$console->list([1=>'red',2=>'blue',3=>'green']);

$selection = $console->getOneOf(null, [1,2,3]);

$console->echo('<primary>You selected <secondary>' . $selection);
