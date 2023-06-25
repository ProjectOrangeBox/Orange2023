<?php

declare(strict_types=1);

namespace dmyers\orange\interfaces;

interface ViewerInterface
{
    public function render(string $view, array $data = []): string;
    public function renderString(string $string, array $data = []): string;
    public function addPath(string $path, bool $first = false): self;
    public function addPaths(array $paths): self;
    public function addPlugin(string $name, mixed $args): self;
    public function addPlugins(array $plugins): self;
}
