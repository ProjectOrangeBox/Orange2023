<?php

declare(strict_types=1);

class collectErrorsFromMe
{
    public function errors(?string $key = null): array
    {
        return ['error 1', 'error 2'];
    }

    public function differentMethod() {
        return ['Big Error 1', 'Big Error 2'];
    }
}
