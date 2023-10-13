<?php

function fig_money(mixed $number = 0)
{
    $number = (float)$number;
    
    $prefix = ($number < 0) ? '-$' : '$';
    
    return $prefix . number_format(abs($number), 2);
}
