<?php

declare(strict_types=1);

class EventClassName
{
    public function EventMethodName(&$arg1, &$arg2): void
    {
        $arg1 = '[' . $arg2 . ']';
    }
}
