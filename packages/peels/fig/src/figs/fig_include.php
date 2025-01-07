<?php

/**
 * include another template
 */
function fig_include(string $view = null, array $data = []): void
{
    echo container()->view->render($view, $data);
}
