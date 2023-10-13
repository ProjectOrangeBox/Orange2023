<?php

/**
 * finish the buffering of a named section
 * 
 * optionally appending to the current data variable
 */
function fig_end(int $append = fig::NONE): void
{
    $figSections = fig::value('_fig##sections_');

    if (count($figSections) == 0) {
        throw new Exception('Cannot end section because you are not in a section.');
    }

    $name = array_pop($figSections);

    fig::set('_fig##sections_', $figSections);

    // capture output buffer
    fig::set($name, ob_get_contents(), $append);

    ob_end_clean();
}
