<?php

declare(strict_types=1);

return [
    // the end user should provide these but the defaults are below
    'config directory' => __ROOT__ . '/config',

    'display_errors'=> 0,
    'display_startup_errors'=> 0,
    'error_reporting'=> 0,
   
    'timezone' => @date_default_timezone_get(),
    'encoding' => 'UTF-8',
    'helpers' => [], // default none
];
