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
    public function findView(string $view): string;
    public function viewExists(string $view): bool;

    // use these to pass the entire name => absolute file path array into the class
    // to stop the foreach looping over the paths
    // this is useful when you are using a cache in production
    public function setViews(array $views): self;
    public function setPlugins(array $plugins): self;
}
