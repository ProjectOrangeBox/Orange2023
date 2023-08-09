<?php

define('__ROOT__', realpath(__DIR__ . '/../../../'));
define('__WWW__', realpath(__DIR__.'/../../../htdocs'));

$_ENV = array_replace_recursive($_ENV, parse_ini_file(__DIR__.'/../unitTests/.env', true, INI_SCANNER_TYPED));