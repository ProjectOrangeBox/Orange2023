<?php

/**
 *
 */
function fig_hasblock(string $name): bool
{
    return in_array($name, fig::value('_fig##blocks_', []));
}
