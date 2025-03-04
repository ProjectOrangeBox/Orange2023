<?php

/**
 * include another template
 */
function fig_modal(string $name, ?string $view, array $data = [], string $size = 'xl'): void
{
    $html = '<div id="modal-bootstrap-' . $name . '" class="modal fade modal-' . $size . '" tabindex="-1"><div class="modal-dialog modal-dialog-scrollable modal-dialog-centered"><div class="modal-content"><div id="modal-bootstrap-updateModal-content" class="modal-body"><div>';
    $html .= container()->view->render($view, $data);
    $html .= '</div></div></div></div></div>';

    echo $html;
}
