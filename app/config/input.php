<?php

declare(strict_types=1);

return [
    'raw' => file_get_contents('php://input'),
    'post' => $_POST,
    'get' => $_GET,
    'request' => $_REQUEST,
    'server' => $_SERVER,
    'files' => $_FILES,
];
