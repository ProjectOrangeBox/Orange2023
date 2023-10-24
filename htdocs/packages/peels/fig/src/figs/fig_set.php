<?php

/**
 * this is helpful because you can add (set) values in data
 * these need to be retrieved with fig::value() because
 * when the view template is loaded the data is already 
 * extracted locally
 */
function fig_set(string $name, mixed $value, int $append = fig::NONE): void
{
    switch ($append) {
        case fig::BEFORE:
            $current = fig::value($name, '');

            container()->data[$name] = $value . $current;
            break;
        case fig::AFTER:
            $current = fig::value($name, '');

            container()->data[$name] = $current . $value;
            break;
        default:
            container()->data[$name] = $value;
            break;
    }
}
