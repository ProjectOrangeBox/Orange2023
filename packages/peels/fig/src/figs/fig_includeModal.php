<?php

/**
 * include another template
 */
function fig_includemodal(?string $name, ?string $view, array|string $data = []): void
{
    // sm, lg, xl - medium size is default
    if (is_string($data)) {
        $modalSize = 'modal-' . $data;
        $data = [];
    } else {
        $modalSize = isset($data['size']) ? 'modal-' . $data['size'] : '';
    }

    echo '<div id="' . str_replace(' ', '', lcfirst(ucwords(preg_replace("/[^ \w]+/", ' ', $name)))) . '" rv-theme-modal-show="' . $name . '" class="modal fade ' . $modalSize . '" role="dialog" tabindex="-1">';
    echo '<div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">';
    echo '<div class="modal-content">';
    echo '<div class="modal-body">';
    echo container()->view->render($view, $data);
    echo '</div></div></div></div>';
}
