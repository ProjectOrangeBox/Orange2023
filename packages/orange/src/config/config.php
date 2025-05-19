<?php

declare(strict_types=1);

return [
    // the end user should provide these but the defaults are below
    'config directory' => __ROOT__ . '/config',
    // search and merge a directory matching ENVIRONMENT
    // if a boolean of true is provided ENVIRONMENT will be used
    // if a boolean of falise is provied NO ENVIRONMENT is used
    // if a string is provided that is used instead of ENVIRONMENT
    'environment' => true,

    // additional configuration the end user can change
    'environment errors config' => [
        'production' => [
            'display errors' => 0,
            'display startup errors' => 0,
            'error reporting' => 0,
        ],
        'development' => [
            'display errors' => 1,
            'display startup errors' => 1,
            'error reporting' => E_ALL,
        ],
        'default' => [
            'display errors' => 1,
            'display startup errors' => 1,
            'error reporting' => E_ALL ^ E_NOTICE,
        ],
    ],
    'timezone' => @date_default_timezone_get(),
    'encoding' => 'UTF-8',
    'helpers' => [], // default none
];
