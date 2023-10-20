<?php

function wrapArray(array $array, string $prefix = '', string $suffix = '', string $separator = ''): string
{
    $output = [];

    foreach ($array as $s) {
        $output[] = $prefix.$s.$suffix;
    }

    return implode($separator,$output);
}
