<?php

/**
 * include another template
 */
function fig_include(?string $view, array $data = []): void
{
    echo container()->view->render($view, $data);
}
