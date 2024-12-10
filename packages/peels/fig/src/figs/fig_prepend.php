<?php

/**
 * prepend to a value already in data see fig::set(...)
 */
function fig_prepend(string $name, string $value): void
{
    fig::set($name, $value, fig::BEFORE);
}
