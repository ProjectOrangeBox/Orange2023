<?php

/**
 * start the buffering of a named block
 * the block names become data variables
 */
function fig_block(string $name): void
{
    fig::set('_fig##blocks_', [$name], fig::AFTER);

    // start output buffering
    ob_start();
}
