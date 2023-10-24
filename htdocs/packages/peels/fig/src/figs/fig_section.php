<?php

/**
 * start the buffering of a named section
 * the section names become data variables
 */
function fig_section(string $name): void
{
    $figSections = fig::value('_fig##sections_', []);

    $figSections[] = $name;

    fig::set('_fig##sections_', $figSections);

    // start output buffering
    ob_start();
}
