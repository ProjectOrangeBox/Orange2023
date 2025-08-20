<?php

declare(strict_types=1);

use orange\framework\Application;

return [
    'get' => Application::fromGlobals('get'),
    'server' => Application::fromGlobals('server'),
    'files' => Application::fromGlobals('files'),
    'cookie' => Application::fromGlobals('cookie'),
    'post' => Application::fromGlobals('post'),
    'request' => Application::fromGlobals('request'),
    'body' => Application::fromGlobals('body'),

    // for cli detection
    // override in your config if needed
    // only 1 is actually needed because both are checked
    'php_sapi' =>  Application::fromGlobals('php_sapi'), // string
    'stdin' =>  Application::fromGlobals('stdin'), // boolean

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
