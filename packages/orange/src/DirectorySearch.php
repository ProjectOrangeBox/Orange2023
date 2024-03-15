<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\exceptions\ResourceNotFound;
use orange\framework\interfaces\DirectorySearchInterface;

class DirectorySearch implements DirectorySearchInterface
{
    // automatically appened extension
    // if this is provided then EVERY search will include it
    // if you are providing the extension in your search then
    // you will want to leave this empty
    protected string $extension;
    // array of directories to search for files
    protected array $directories;
    // array of "found" files
    // these are only found IF they are requested
    protected array $resources;

    protected bool $quiet;

    public function __construct(array $config)
    {
        $this->directories = $config['directories'] ?? [];
        $this->resources = $config['resources'] ?? [];
        $this->quiet = $config['quiet'] ?? false;

        if (isset($config['extension'])) {
            $this->extension($config['extension']);
        }
    }

    /* getter & setter */
    public function extension(string $extension = null): string
    {
        if (is_string($extension)) {
            $this->extension = $extension;
        }

        return $this->extension;
    }

    public function addDirectory(string $directory, bool $prepend = false): self
    {
        // path is added without checking if it's there for various reasons
        // path is also used as the array key so there aren't any duplicates
        // and you can "move" a previously added path to the "top" is necessary
        $directory = rtrim($directory, DIRECTORY_SEPARATOR);

        if ($prepend) {
            // add to beginning of search array
            array_unshift($this->directories, $directory);
        } else {
            // append to the end of search array
            array_push($this->directories, $directory);
        }

        return $this;
    }

    public function addDirectories(array $directories, bool $prepend = false): self
    {
        foreach ($directories as $directory) {
            $this->addDirectory($directory, $prepend);
        }

        return $this;
    }

    public function removeDirectory(string $directory, bool $removeFound = true): self
    {
        if (isset($this->directories[$directory])) {
            unset($this->directories[$directory]);

            if ($removeFound) {
                $dirLength = strlen($directory);

                foreach ($this->resources as $name => $path) {
                    if (substr($path, 0, $dirLength) == $directory) {
                        unset($this->resources[$name]);
                    }
                }
            }
        }

        return $this;
    }

    public function removeDirectories(array $directories, bool $removeFound = true): self
    {
        foreach ($directories as $directory) {
            $this->removeDirectory($directory, $removeFound);
        }

        return $this;
    }

    public function list(): array
    {
        return $this->directories;
    }

    public function replace(array $directories, bool $removeFound = true): self
    {
        $this->directories = $directories;

        if ($removeFound) {
            $this->resources = [];
        }

        return $this;
    }

    // find all
    public function findAll(string $resource): array
    {
        // did we find it?
        if (!$this->exists($resource) && !$this->quiet) {
            throw new ResourceNotFound($resource);
        }
        
        return $this->resources[$resource] ?? [];
    }

    // find first
    public function find(string $resource): string
    {
        // did we find it?
        if (!$this->exists($resource) && !$this->quiet) {
            throw new ResourceNotFound($resource);
        }

        return $this->resources[$resource][0] ?? '';
    }


    public function exists(string $resource): bool
    {
        $this->search($resource);

        return isset($this->resources[$resource]);
    }

    protected function search(string $resource): void
    {
        // finds all that match the resource
        if (!isset($this->resources[$resource])) {
            foreach ($this->directories as $directory) {
                if ($fullpath = realpath(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($resource, DIRECTORY_SEPARATOR) . $this->extension)) {
                    $this->resources[$resource][] = $fullpath;
                }
            }
        }
    }
}
