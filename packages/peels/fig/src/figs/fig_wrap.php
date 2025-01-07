<?php

function fig_wrap(array|string $input, string $prefix = '', string $suffix = '', bool $escape = true)
{
    if (is_string($input)) {
        $html = $prefix . (($escape) ? htmlentities($input) : $input) . $suffix;
    } else {
        $elements = [];

        foreach ($input as $text) {
            $elements[] = fig_wrap($text, $prefix, $suffix, $escape);
        }

        $html = implode('', $elements);
    }

    return $html;
}
