<?php

function fig_date($timestamp = null, $format = null)
{
    $format = (!empty($format)) ? $format : config('application','human date', 'F jS, Y, g:i a');
    
    if ($timestamp == 'now') {
        $timestamp = time();
    } else {
        $timestamp = (is_integer($timestamp)) ? $timestamp : strtotime($timestamp);
    }

    return ($timestamp > 1000) ? date($format, $timestamp) : '';
}
