<?php

declare(strict_types=1);

namespace orange\framework\interfaces;

interface DispatcherInterface
{
    public const CONTROLLER = 0;
    public const METHOD = 1;

    public function call(array $routeMatched): string;
}
