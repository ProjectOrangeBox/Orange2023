<?php

declare(strict_types=1);

namespace orange\framework\interfaces;

interface DispatcherInterface
{
    public function call(array $routeMatched): string;
}
