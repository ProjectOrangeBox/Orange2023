#!/usr/bin/env php
<?php

declare(strict_types=1);

define('__ROOT__', realpath(__DIR__ . '/../'));
chdir(__ROOT__);

require_once __ROOT__ . '/vendor/autoload.php';

mergeEnv(__ROOT__ . '/.env');

/* send config into application */
$c = cli(include __ROOT__ . '/app/config/config.php');

$c->console->line();

// all services are now available!
$c->console->echo('<yellow>This is <green>a <yellow>test');

$c->console->minimumArguments(1, 'Please provide the filename.');

$filename = $c->console->getArgument(1);

$c->console->echo('<red>Using File <white>' . $filename);

$color = $c->console->getArgumentByOption('-color');

$c->console->echo('<red>Using Color <white>' . $color);

$last = $c->console->getLastArgument();

$c->console->echo('<red>Last <white>' . $last);

$arg1 = $c->console->getArgument(1);

$c->console->echo('<red>Arg 1 <white>' . $arg1);

$table = [
    ['Colors','Names','Age'],
    ['Red','Johnny Apple',23],
    ['Purple','Jenny Smith',23],
    ['Yellow','Jake Louder',23],
    ['Yellow','Jack Black',857],
];

$c->console->table($table);

$name = $c->console->getLine('<yellow>What is your name?');

$c->console->echo('<purple>Hello <normal>' . $name);

$c->console->list([1=>'red',2=>'blue',3=>'green']);

$selection = $c->console->getOneOf(null, [1,2,3]);

$c->console->echo('<purple>You selected <normal>' . $selection);
