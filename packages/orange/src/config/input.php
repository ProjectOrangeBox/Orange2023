<?php

declare(strict_types=1);

// read $_ENV using fetchEnv();
return [
    'raw' => file_get_contents('php://input'),
    'post' => $_POST,
    'get' => $_GET,
    'request' => $_REQUEST,
    'server' => $_SERVER,
    'files' => $_FILES,
    'cookie' => $_COOKIE,
    'config' => [
        'convert keys to' => 'lowercase',
        'filter keys' => FILTER_SANITIZE_SPECIAL_CHARS,
        'filter keys flag' => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK | FILTER_FLAG_ENCODE_HIGH,
        'valid input keys' => ['post', 'get', 'request', 'server', 'file', 'raw', 'cookie'],
    ],
];
