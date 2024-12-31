<?php

/**
 *
 */
function fig_inblock(): bool
{
    return (count(fig::value('_fig##blocks_', [])) > 0);
}
