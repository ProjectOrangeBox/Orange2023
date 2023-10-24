<?php

declare(strict_types=1);

namespace dmyers\orange\interfaces;

interface DataInterface
{
    public function merge(array $replaceArray): self;
}
