<?php

/**
 * shorthand version of value
 */
function fig_v(string $name, mixed $default = ''): mixed
{
    return fig::value($name, $default);
}