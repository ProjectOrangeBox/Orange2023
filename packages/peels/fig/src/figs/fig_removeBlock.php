<?php

/**
 *
 */
function fig_removeblock(string $name): void
{
    $blocks = fig::value('_fig##blocks_', []);

    unset($blocks[$name]);

    fig::set('_fig##blocks_', $blocks);
}
