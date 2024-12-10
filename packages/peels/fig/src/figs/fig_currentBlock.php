<?php

/**
 *
 */
function fig_currentblock(string $name): string
{
    $blocks = fig::value('_fig##blocks_', []);

    return end($blocks);
}
