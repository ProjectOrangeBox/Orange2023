<?php

declare(strict_types=1);

define('__ROOT__', realpath(__DIR__ . '/../'));
define('__WWW__', realpath(__DIR__));

chdir(__ROOT__);

require_once __ROOT__ . '/vendor/autoload.php';

mergeEnv(__ROOT__.'/.env');

/* send config into application */
http(include __ROOT__ . '/app/config/config.php');
