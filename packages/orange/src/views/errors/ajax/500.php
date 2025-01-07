<?php

$args = [];

if (isset($code) && $code > 0) {
    $args['code'] = $code;
}
if (isset($message)) {
    $args['message'] = $message;
}
if (isset($file)) {
    $args['file'] = $file;
}
if (isset($line)) {
    $args['line'] = $line;
}
if (!empty($options)) {
    $args['options'] = print_r($options, true);
}

echo json_encode($args, JSON_PRETTY_PRINT);
