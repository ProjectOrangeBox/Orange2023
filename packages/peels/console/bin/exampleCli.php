#!/usr/bin/env php
<?php

declare(strict_types=1);

use peels\console\Console;
use PSpell\Config;

$container = require __DIR__ . '/../../../../bootstrapCli.php';

// start shellscript
$console = $container->console;

$console->detectVerboseLevel();
//$console->verbose(Console::EVERYTHING);

$console->echo('output info', Console::INFO); // single -v (always required to trigger verbose)

// optional
$console->echo('output notice', Console::NOTICE);
$console->echo('output warning', Console::WARNING);
$console->echo('output error', Console::ERROR);
$console->echo('output critical', Console::CRITICAL);
$console->echo('output alert', Console::ALERT);
$console->echo('output emergency', Console::EMERGENCY);
$console->echo('output debug', Console::DEBUG);
$console->echo('output always', Console::ALWAYS);

$console->primary('Primary');
$console->secondary('Secondary');
$console->success('Success');
$console->danger('Danger');
$console->warning('Warning');
$console->info('Info');
$console->error('Error');
$console->stop('Stop');
