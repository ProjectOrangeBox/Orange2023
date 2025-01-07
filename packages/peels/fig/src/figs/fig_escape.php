<?php

/**
 * wrapper for escaping html
 */
function fig_escape(string $name, bool $lookup = true): string
{
    $html = ($lookup) ? fig::value($name) : $name;

    return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
}
