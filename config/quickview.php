<?php

return [

    /* merged content below */
    '500' => ['contentType' => 'json', 'charSet' => 'UTF-8', 'responseCode' => 500, 'send' => true],

    '406' => ['contentType' => 'json', 'charSet' => 'UTF-8', 'responseCode' => 406, 'template' => '<?=json_encode($json);'],
    '201' => ['contentType' => 'json', 'charSet' => 'UTF-8', 'responseCode' => 201, 'send' => true],
    '202' => ['contentType' => 'json', 'charSet' => 'UTF-8', 'responseCode' => 202, 'send' => true],

    'list' => ['contentType' => 'json', 'charSet' => 'UTF-8', 'responseCode' => 200, 'template' => '<?=json_encode($json);'],

    'exception' => ['contentType' => 'json', 'charSet' => 'UTF-8', 'responseCode' => 500, 'send' => true, 'template' => '<?=json_encode($exception,JSON_PRETTY_PRINT);', 'exit' => true],
    'validate failed' => ['contentType' => 'json', 'charSet' => 'UTF-8', 'responseCode' => 406, 'template' => '<?=json_encode($json);', 'send' => true, 'exit' => true],
    /* end merged contents */

];
