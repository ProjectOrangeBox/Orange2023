<?php

/**
 * allow some basic formatting with optional
 * if the value is X don't show
 */
function fig_hiddenif(string $format = '', string $value = '', string $considerEmpty = ''): string
{
    $html = '';

    if ($value != $considerEmpty) {
        $html = fig::escape(sprintf($format, $value));
    }

    return $html;
}
