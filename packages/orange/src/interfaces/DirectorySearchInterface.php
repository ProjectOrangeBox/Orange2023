<?php

declare(strict_types=1);

namespace orange\framework\interfaces;

interface DirectorySearchInterface
{
    public function addDirectory(string $directory, bool $prepend = false): self;
    public function addDirectories(array $directories, bool $prepend = false): self;
    public function removeDirectory(string $directory, bool $removeFound = false): self;
    public function removeDirectories(array $directories, bool $removeFound = false): self;
    public function list(): array;
    public function replace(array $directories, bool $removeFound = false): self;

    // throws an exception if not found
    public function find(string $resource): string;
    public function findAll(string $resource): array;
    public function exists(string $resource): bool;

    /* auto added file extension (with .) if any */
    public function extension(string $extension = null): string;
}
