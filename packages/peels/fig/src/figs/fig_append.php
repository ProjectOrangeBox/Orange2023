<?php

/**
 * append to a value already in data see fig::set(...)
 */
function fig_append(string $name, string $value): void
{
    fig::set($name, $value, fig::AFTER);
}
