<?php

/**
 * shorter syntax for escaping html
 */
function fig_e(string $name): string
{
    return fig::escape($name);
}
