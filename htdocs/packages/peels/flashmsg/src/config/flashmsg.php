<?php

// default grab from $_SESSION
// but you should pass the "real" HTTP_REFERER from 
// the container class input in as part of the config
$refer = (isset($_SESSION['HTTP_REFERER'])) ? $_SESSION['HTTP_REFERER'] : '';

return [
    'sticky types' => ['red', 'danger', 'warning', 'yellow'],
    'initial pause' => 3,
    'pause for each' => 1000,
    'default type' => 'info',
    'http referer' => $refer,
    'view variable' => 'messages',
    'session msg key'=> '__#internal::flash::msg#__',
];
