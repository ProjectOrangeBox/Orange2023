<?php

/**
 * allow some basic formatting with optional
 * if the value is X show
 */
function fig_shownif(string $format = '', string $value = '', string $considerNotEmpty = ''): string
{
    $html = '';

    if ($value != $considerNotEmpty) {
        $html = fig::escape(sprintf($format, $value));
    }

    return $html;
}
