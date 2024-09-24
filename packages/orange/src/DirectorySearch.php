<?php

declare(strict_types=1);

namespace orange\framework;

use Closure;
use orange\framework\exceptions\NotFound;
use orange\framework\exceptions\ClassLocked;
use orange\framework\exceptions\ResourceNotFound;
use orange\framework\interfaces\DirectorySearchInterface;
use orange\framework\exceptions\filesystem\DirectoryNotFound;

class DirectorySearch implements DirectorySearchInterface
{
    // wildcard match for files (glob syntax)
    protected string $match;

    // array of directories to search for files
    protected array $directories;

    // array of "found" files
    // these are only found IF they are requested
    protected array $resources;

    // throw exception if resource not found?
    protected bool $quiet;

    // search recursively in the directories?
    protected bool $recursive;

    // track if we need to run scanDirectories again
    protected bool $rescan;

    // ignore adding directories
    protected bool $locked;

    // closure to extract the resource name from the path
    protected Closure $extractResourceKey;

    // lock the class (from add / remove) after the first full scan is done
    protected bool $lockAfterScan;

    // make all keys lowercase
    protected bool $lowercaseKeys;

    // prepend file by default
    protected bool $prepend;

    // callback method
    protected array $callback;

    // not a standalone class and not a singleton
    public function __construct(array $config)
    {
        $this->match = $config['match'] ?? '*.php';
        $this->quiet = $config['quiet'] ?? false;
        $this->lowercaseKeys = $config['normalize keys'] ?? true;
        $this->recursive = $config['recursive'] ?? false;
        $this->locked = $config['locked'] ?? false;
        $this->lockAfterScan = $config['lock after scan'] ?? false;
        $this->prepend = $config['prepend'] ?? true;
        $this->callback = $config['callback'] ?? [];

        $this->rescan();

        /*
        $fileInfo:
            ["dirname"]=> "/home/johnnyAppleseed/Sites/orange/application/welcome/views/test"
            ["basename"]=> "uploadForm.php"
            ["extension"]=> "php"
            ["filename"]=> "uploadForm"
            ["searchpath"]=> "/home/johnnyAppleseed/Sites/orange/application/welcome/views"
        */

        // the built in resource key extractor based on the complete resource file path
        switch ($config['resource key style'] ?? 'view') {
            case 'filename':
            case 'config':
                $this->extractResourceKey = function ($fileInfo) {
                    return $fileInfo['filename'];
                };
                break;
            case 'basename':
                $this->extractResourceKey = function ($fileInfo) {
                    return $fileInfo['basename'];
                };
                break;
            case 'fullpath':
                $this->extractResourceKey = function ($fileInfo) {
                    return $fileInfo['dirname'] . DIRECTORY_SEPARATOR . $fileInfo['basename'];
                };
                break;
            case 'localpath':
                $this->extractResourceKey = function ($fileInfo) {
                    return substr($fileInfo['dirname'] . DIRECTORY_SEPARATOR . $fileInfo['basename'], strlen($fileInfo['searchpath']) + 1);
                };
                break;
            case 'view':
            default:
                $this->extractResourceKey = function ($fileInfo) {
                    return substr($fileInfo['dirname'] . DIRECTORY_SEPARATOR . $fileInfo['basename'], strlen($fileInfo['searchpath']) + 1, -strlen($fileInfo['extension']) - 1);
                };
                break;
        }

        $this->flushDirectories(true)->addDirectories($config['directories'] ?? [])->addResources($config['resources'] ?? []);
    }

    /**
     * any new directory added will take presentence over any previous directories (prepend)
     */
    public function addDirectory(string $directory, bool|int $prepend = null): self
    {
        // should we throw an exception?
        $this->ifLockedThrowException();

        if ($found = realpath(rtrim($directory, DIRECTORY_SEPARATOR))) {
            $prepend = $this->determinePrepend($prepend);

            if ($prepend) {
                $this->directories = [$found => null] + $this->directories;
            } else {
                $this->directories[$found] = null;
            }

            // force a rescan on next read
            $this->rescan();
            $this->callback();
        } elseif (!$this->quiet) {
            throw new DirectoryNotFound($directory);
        }

        return $this;
    }

    /**
     * any new directories as a "group" added will take presentence over any previous directories (prepend)
     */
    public function addDirectories(array $directories, bool|int $prepend = null): self
    {
        $prepend = $this->determinePrepend($prepend);

        if ($prepend) {
            $directories = array_reverse($directories);
        }

        foreach ($directories as $directory) {
            $this->addDirectory($directory, $prepend);
        }

        return $this;
    }

    /**
     * remove if it matches the directory
     */
    public function removeDirectory(string $directory, bool $removeFoundResources = true): self
    {
        $this->ifLockedThrowException();

        $directory = realpath(rtrim($directory, DIRECTORY_SEPARATOR));

        unset($this->directories[$directory]);

        if ($directory && $removeFoundResources) {
            $dirLength = strlen($directory);

            foreach ($this->resources as $resource => $path) {
                if (substr($path, 0, $dirLength) == $directory) {
                    unset($this->resources[$resource]);
                }
            }
        }

        $this->callback();

        return $this;
    }

    public function removeDirectories(array $directories, bool $removeFoundResources = true): self
    {
        foreach ($directories as $directory) {
            $this->removeDirectory($directory, $removeFoundResources);
        }

        return $this;
    }

    public function listDirectories(): array
    {
        return array_keys($this->directories);
    }

    public function replaceDirectories(array $directories, bool $removeFoundResources = true): self
    {
        $this->ifLockedThrowException();

        $this->flushDirectories();

        // replace them verbatim
        $this->addDirectories($directories);

        if ($removeFoundResources) {
            $this->resources = [];
        }

        $this->rescan();
        $this->callback();

        return $this;
    }

    public function directoryExists(string $directory): bool
    {
        return array_key_exists(realpath(rtrim($directory, DIRECTORY_SEPARATOR)), $this->directories);
    }

    public function flushDirectories(bool $flushResources = true): self
    {
        $this->directories = [];

        if ($flushResources) {
            $this->flushResources();
        }

        $this->callback();

        return $this;
    }

    /* resources */

    public function addResource(string $resource, string $path): self
    {
        if ($path = realpath($path)) {
            // there may actually be multiple matching resources for 1 resource key
            $this->resources[$this->normalizeKey($resource)][$path] = null;
        }

        $this->callback();

        return $this;
    }

    public function addResources(array $resources): self
    {
        foreach ($resources as $resource => $path) {
            $this->addResource($resource, $path);
        }

        return $this;
    }

    public function replaceResources(array $resources): self
    {
        return $this->flushResources()->addResources($resources);
    }

    public function flushResources(): self
    {
        $this->resources = [];

        $this->callback();

        return $this;
    }

    /**
     * find all
     */
    public function find(string $resource): array
    {
        $found = [];

        // search for all resources and put in $this->resources
        $this->scanDirectories();

        // we are looking for a specific resource
        if ($this->exists($resource)) {
            $found = array_keys($this->resources[$this->normalizeKey($resource)]);
        } elseif (!$this->quiet) {
            throw new ResourceNotFound($resource);
        }

        return $found;
    }

    /**
     * find all resources
     */
    public function findAll(): array
    {
        $found = [];

        // search for all resources and put in $this->resources
        $this->scanDirectories();

        foreach ($this->resources as $resourceName => $resources) {
            $found[$resourceName] = array_keys($resources);
        }

        return $found;
    }

    public function listResources(): array
    {
        $this->scanDirectories();

        return array_keys($this->resources);
    }

    /**
     * find first
     */
    public function findFirst(string $resource): string
    {
        $found = $this->find($resource);

        return $found[array_key_first($found)];
    }

    /**
     * find last
     */
    public function findLast(string $resource): string
    {
        $found = $this->find($resource);

        return $found[array_key_last($found)];
    }

    /**
     * exists anywhere
     */
    public function exists(string $resource): bool
    {
        $this->scanDirectories();

        return array_key_exists($this->normalizeKey($resource), $this->resources);
    }

    public function lock(): self
    {
        $this->locked = true;

        return $this;
    }

    public function unlock(): self
    {
        $this->locked = false;

        return $this;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function __debugInfo()
    {
        return ['resources' => $this->resources, 'directories' => $this->directories];
    }

    /* protected */

    protected function scanDirectories(): void
    {
        if ($this->rescan) {
            // do directory scan for resources append new resources
            foreach (array_keys($this->directories) as $directory) {
                if ($searchPath = realpath(rtrim($directory, DIRECTORY_SEPARATOR))) {
                    // search up to a maximum of 8 levels deep
                    if ($this->recursive) {
                        $this->addMatches($searchPath, glob($searchPath . '/{,*/,*/*/,*/*/*/,*/*/*/*/,*/*/*/*/*/,*/*/*/*/*/*/,*/*/*/*/*/*/*/,*/*/*/*/*/*/*/*/}' . $this->match, GLOB_BRACE));
                    } else {
                        $this->addMatches($searchPath, glob($searchPath . '/' . $this->match));
                    }
                }
            }

            if ($this->lockAfterScan) {
                $this->lock();
            }

            $this->rescan = false;
        }
    }

    protected function addMatches(string $searchPath, array|false $matches): void
    {
        if (is_array($matches)) {
            foreach ($matches as $file) {
                $fileInfo = pathinfo($file);
                $fileInfo['searchpath'] = $searchPath;
                $closureFunction = $this->extractResourceKey;
                $key = $closureFunction($fileInfo);
                $this->addResource($key, $file);
            }
        }
    }

    protected function ifLockedThrowException(): void
    {
        if ($this->locked) {
            throw new ClassLocked(__CLASS__);
        }
    }

    protected function normalizeKey(string $key): string
    {
        return ($this->lowercaseKeys) ? mb_strtolower($key) : $key;
    }

    protected function determinePrepend(null|int|bool $arg): bool
    {
        $prepend = $this->prepend;

        if ($arg === self::FIRST) {
            $prepend = true;
        } elseif ($arg === self::LAST) {
            $prepend = false;
        } elseif (is_bool($arg)) {
            $prepend = $arg;
        }

        return $prepend;
    }

    protected function rescan(): self
    {
        $this->rescan = true;

        return $this;
    }

    protected function callback(): self
    {
        if (!empty($this->callback)) {
            if (!is_object($this->callback[0]) || !method_exists($this->callback[0], $this->callback[1])) {
                throw new NotFound('Could not call Directory Search Callback');
            }

            call_user_func($this->callback);
        }

        return $this;
    }
}
