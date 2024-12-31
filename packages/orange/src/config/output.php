<?php

declare(strict_types=1);

return [
    'contentType' => 'text/html',
    'charSet' => 'utf-8',
    'language' => 'en',
    'send length' => false,
    'default redirect code' => 301,
    'force http response code' => 301,
    'force https' => false,
    'mimes' => require __DIR__ . '/mimes.php',
    'status codes' => require __DIR__ . '/statusCodes.php',
];
