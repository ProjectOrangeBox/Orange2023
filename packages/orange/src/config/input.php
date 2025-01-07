<?php

declare(strict_types=1);

// lowercase all keys to normalize everything
return [
    'get' => $_GET,
    'server' => $_SERVER,
    'files' => $_FILES,
    'cookie' => $_COOKIE,
    'post' => $_POST,
    'request' => $_REQUEST,
    'body' => file_get_contents('php://input'),

    // for cli detection
    // override in your config if needed
    // only 1 is actually needed because both are checked
    'php_sapi' => PHP_SAPI, // string
    'stdin' => defined('STDIN'), // boolean

    // try to auto detect if the body is json or not
    'auto detect body' => true,

    // these are the input keys which will return values
    // this should match the keys specified above
    'valid input keys' => ['get', 'server', 'files', 'cookie', 'post', 'request', 'body'],

    // these are the input configuration values which are replaceable using replace(...)
    'replaceable input keys' => ['get', 'server', 'files', 'cookie', 'post', 'request', 'body', 'php_sapi', 'stdin'],

    'remap keys' => [
        'put' => 'body',
        'delete' => 'get',
        'http' => 'server',
    ],
];
