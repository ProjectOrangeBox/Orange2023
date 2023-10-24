<?php

/**
 * pull something from the data container
 * 
 * since data is already "extracted" into the view anyway as proper local varaiables
 * this is really just a wrapper for that.
 */
function fig_value(string $name, mixed $default = ''): mixed
{
    return container()->data[$name] ?? $default;
}