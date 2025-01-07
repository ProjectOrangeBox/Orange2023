<?php

declare(strict_types=1);

namespace orange\framework\interfaces;

interface DataInterface
{
    public function merge(array $array, bool $recursive = true, bool $replace = true): static;
}
