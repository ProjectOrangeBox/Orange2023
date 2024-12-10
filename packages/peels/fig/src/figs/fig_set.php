<?php

/**
 * this is helpful because you can add (set) values in data
 * these need to be retrieved with fig::value() because
 * when the view template is loaded the data is already
 * extracted locally
 */
function fig_set(string $name, mixed $value, int $append = fig::NORMAL): void
{
    // get data service the universal application storage
    $dataService = container()->data;

    switch ($append) {
        case fig::BEFORE:
            if (is_array($value)) {
                $dataService[$name] = $value + fig::value($name, []);
            } else {
                $dataService[$name] = $value . fig::value($name, '');
            }
            break;
        case fig::AFTER:
            if (is_array($value)) {
                $dataService[$name] = fig::value($name, []) + $value;
            } else {
                $dataService[$name] = fig::value($name, '') . $value;
            }
            break;
        default:
            // overwrite
            $dataService[$name] = $value;
            break;
    }
}
