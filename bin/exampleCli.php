#!/usr/bin/env php
<?php

declare(strict_types=1);

define('__ROOT__', realpath(__DIR__ . '/../'));

chdir(__ROOT__);

require_once __ROOT__ . '/vendor/autoload.php';

mergeEnv(__ROOT__.'/.env');

/* send config into application */
$container = cli(include __ROOT__ . '/app/config/config.php');

// all services are now available!

$container->console->echo('<red>Hello World');

$container->console->echo('<yellow>This is <green>a <yellow>test');

$container->console->echo('<normal>The config for view is:');

$container->console->echo('<blue>'.print_r($container->config->view,true));

$container->console->echo('<normal>The config for input is:');

$container->console->echo('<blue>'.print_r($container->config->input,true));
