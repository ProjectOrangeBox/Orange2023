<?php

declare(strict_types=1);

namespace dmyers\orange\interfaces;

interface DispatcherInterface
{
    public function call(RouterInterface $route): OutputInterface;
}
