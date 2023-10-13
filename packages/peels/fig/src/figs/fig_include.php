<?php

/**
 * include another template
 */
function fig_include(string $view = null): void
{
    echo container()->view->render($view);
}
