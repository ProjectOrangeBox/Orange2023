<?php

declare(strict_types=1);

// read $_ENV using fetchAppEnv();
return [
    // post, put, delete
    'body' => file_get_contents('php://input'),
    'get' => $_GET,

    // fixed keys
    'server' => $_SERVER,
    'files' => $_FILES,
    'cookie' => $_COOKIE,

    'valid input keys' => ['body',  'get', 'server', 'files', 'cookie'],

    // for cli detection override config if needed
    'PHP_SAPI' => strtoupper(PHP_SAPI),
    'STDIN' => defined('STDIN'),
];
