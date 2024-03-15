<?php

declare(strict_types=1);

return [
    'body' => file_get_contents('php://input'),

    'get' => $_GET,
    'server' => $_SERVER,
    'files' => $_FILES,
    'cookie' => $_COOKIE,
    'post' => $_POST,
    'request' => $_REQUEST,

    'get search keys' => ['body', 'get', 'server', 'files', 'cookie', 'post', 'request'],

    // for cli detection override config if needed
    'PHP_SAPI' => PHP_SAPI, // string
    'STDIN' => defined('STDIN'), // boolean
];
