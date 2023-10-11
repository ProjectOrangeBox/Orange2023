<?php

function sendValidationErrors(array $errors): string
{
    $html = '';

    if (count($errors)) {
        $output = container()->output;
        
        $output->flushAll()->responseCode(406)->contentType('json');

        $html = json_encode((object)['errors' => implode('<br>', $errors)]);
    }

    return $html;
}
