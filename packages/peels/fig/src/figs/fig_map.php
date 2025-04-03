<?php

function fig_map(string $value, array $map): string
{
    if (!in_array($value, $map)) {
        throw new Exception('Cannot locate "' . $value . '" in map.');
    }

    return $map[$value];
}
