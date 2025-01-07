<?php

/**
 * this is helpful because you can add (set) values in data
 * these need to be retrieved with fig::value() because
 * when the view template is loaded the data is already
 * extracted locally
 */
function fig_set(string $name, mixed $value, int $append = fig::NORMAL): void
{
    logMsg('INFO', __METHOD__ . ' ' . $name);

    if (!isset(fig::$data[$name])) {
        if (is_array($value)) {
            fig::$data[$name] = [];
        } else {
            fig::$data[$name] = '';
        }
    }

    switch ($append) {
        case fig::BEFORE:
            if (is_array($value)) {
                fig::$data[$name] = $value + fig::value($name, []);
            } else {
                fig::$data[$name] = $value . fig::value($name, '');
            }
            break;
        case fig::AFTER:
            if (is_array($value)) {
                fig::$data[$name] = fig::value($name, []) + $value;
            } else {
                fig::$data[$name] = fig::value($name, '') . $value;
            }
            break;
        default:
            // overwrite
            fig::$data[$name] = $value;
            break;
    }
}
