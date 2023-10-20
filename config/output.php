<?php

declare(strict_types=1);

return [
    // default content type
    'contentType' => 'text/html',
    // default cahracter set
    'charSet' => 'utf-8',
    // send length
    'send length' => true,

    'predefined'=> [
        '406' => ['contentType' => 'json', 'charSet' => 'UTF-8', 'responseCode' => 406],
        '201' => ['contentType' => 'json', 'charSet' => 'UTF-8', 'responseCode' => 201],
        '202' => ['contentType' => 'json', 'charSet' => 'UTF-8', 'responseCode' => 202],
    ],
];
