#!/usr/bin/env php
<?php

declare(strict_types=1);

require '../bootstrapCli.php';

// start shellscript
$console = $container->console;

// turn on to level or all if nothing included
$console->turnOnOutput();

$console->line();

$appConfig = $container->config->app;

$console->primary('h1 is '.$appConfig['h1']);
$console->primary('file is '.$appConfig['file']);

$console->primary('This is a primary test');
$console->secondary('This is a secondary test');

$console->line();

$console->error('Danger, Will Robinson!');
$console->success('Task Success!');
$console->info('Important Information!');
$console->warning('Warning! System Overload!');

$console->line();

$console->minimumArguments(1, 'You have no provided a filename to open.');

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
