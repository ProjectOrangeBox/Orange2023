<?php

declare(strict_types=1);

// read $_ENV using fetchAppEnv();
return [
    'raw' => file_get_contents('php://input'),
    'post' => $_POST,
    'get' => $_GET,
    'request' => $_REQUEST,
    'server' => $_SERVER,
    'files' => $_FILES,
    'cookie' => $_COOKIE,

    'convert keys to' => 'lowercase',
    're key filter' => '@[^a-z0-9 \[\]\-_]+@',
    'valid input keys' => ['post', 'get', 'request', 'server', 'file', 'raw', 'cookie'],

    // for cli detection override config if needed
    'PHP_SAPI' => strtoupper(PHP_SAPI),
    'STDIN' => defined('STDIN'),
];
