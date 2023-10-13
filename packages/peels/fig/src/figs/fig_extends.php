<?php

/**
 * This is used to extend a "base" template
 * that way you can declare at the top (or really any where) of 
 * you template what "base" template you are extending
 * using fig::section(...) you can then add or append to different
 * data variables
 * when the page is "finished" fig::finish() the parent template 
 * you are extending is loaded and your sections merged
 */
function fig_extends(string $view): void
{
    $extending = fig::value('_fig##extends_', null);

    if ($extending !== null) {
        throw new Exception('Cannot extend a base template because you are already extending "' . $extending . '".');
    }

    fig::set('_fig##extends_', $view);
}
