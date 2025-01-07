<?php

/**
 * apply the extended template
 */
function fig_render(): void
{
    $extending = fig::value('_fig##extends_', null);

    if ($extending === null) {
        throw new Exception('Cannot render a extended view because you aren\'t extending one.');
    }

    // render this out to the view buffering
    fig::include($extending);

    fig::set('_fig##extends_', null);
}
