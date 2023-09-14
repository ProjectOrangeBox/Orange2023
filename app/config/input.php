<?php

declare(strict_types=1);

/**
 * Attach all of the global input to pass them into the handler
 * 
 * By doing this it is easier to do unit testing
 *
 */

return [
    'raw' => file_get_contents('php://input'),
    'post' => $_POST,
    'get' => $_GET,
    'request' => $_REQUEST,
    'server' => $_SERVER,
    'files' => $_FILES,
];
