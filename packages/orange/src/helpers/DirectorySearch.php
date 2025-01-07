<?php

declare(strict_types=1);

namespace orange\framework\helpers;

use Closure;
use orange\framework\exceptions\NotFound;
use orange\framework\exceptions\ClassLocked;
use orange\framework\traits\ConfigurationTrait;
use orange\framework\exceptions\ResourceNotFound;
use orange\framework\interfaces\DirectorySearchInterface;
use orange\framework\exceptions\filesystem\DirectoryNotFound;

class DirectorySearch implements DirectorySearchInterface
{
    use ConfigurationTrait;

    // interface constants: FIRST, LAST, PREPEND, APPEND

    // wildcard match for files (glob syntax)
    protected string $match = '';

    // array of directories to search for files
    protected array $directories = [];

    // array of "found" files
    protected array $resources = [];

    // throw exception if resource not found?
    protected bool $quiet = false;

    // search recursively in the directories?
    protected bool $recursive = false;

    // track if we need to run scanDirectories again
    protected bool $rescan = true;

    // ignore adding directories / resources
    protected bool $locked = false;

    // closure to extract the resource name from the path
    protected Closure $keyClosure;

    // lock the class (from add / remove) after the first full scan is done
    protected bool $lockAfterScan = false;

    // normalize keys?
    protected bool $normalizeKeys = true;

    // hash keys
    protected bool $hashKeys = true;

    // append or prepend by default?
    protected int $pend = self::FIRST;

    // callback method
    protected array $callback = [];

    protected array $defaults = [
        'match' => '*.php', // glob format
        'quiet' => false, // throw exceptions when resource not found?
        'normalize keys' => true,
        'hash keys' => false, // if your keys are large is it helpful to hash them instead
        'recursive' => false, // recursive search directories
        'locked' => false, // does it start locked?
        'lock after scan' => false, // lock after first scan (read)
        'pend' => DirectorySearchInterface::PREPEND, // append or prepend new directories to search list?
        'callback' => [], // class::method
        'resource key style' => 'view', // can also be a custom closure
        'directories' => [], // startup defaults
        'resources' => [],
    ];

    // not a standalone class and not a singleton
    public function __construct(array $config)
    {
        $config = array_replace($this->defaults, $config);

        // assign class properties based on config values where applicable
        $this->assignFromConfig($config);

        // indicate we need a rescan on the next read call
        $this->rescan();

        // setup the resource key style
        $this->setupResourceKeyStyle($config);

        // add any defaults?
        $this->flushDirectories(true)->addDirectories($config['directories'])->addResources($config['resources']);
    }

    protected function setupResourceKeyStyle(array $config): void
    {
        /*
        passed fileinfo
          fileInfo:
            dirname = "/home/johnnyAppleseed/Sites/orange/application/welcome/views/test"
            basename = "uploadForm.php"
            extension = "php"
            filename = "uploadForm"
            searchpath = "/home/johnnyAppleseed/Sites/orange/application/welcome/views"
        */

        if (is_closure($config['resource key style'])) {
            $this->keyClosure = $config['resource key style'];
        } else {
            // the built in resource key extractor based on the complete resource file path
            switch ($config['resource key style']) {
                case 'filename':
                case 'config':
                    // The key will be the filename ie. uploadForm
                    $this->keyClosure = function ($fileInfo) {
                        return $fileInfo['filename'];
                    };
                    break;
                case 'basename':
                    // The key will be the basename ie. uploadForm.php
                    $this->keyClosure = function ($fileInfo) {
                        return $fileInfo['basename'];
                    };
                    break;
                case 'fullpath':
                    // The key will be the dirname + basename ie. /home/johnnyAppleseed/Sites/orange/application/welcome/views/test/uploadForm.php
                    $this->keyClosure = function ($fileInfo) {
                        return $fileInfo['dirname'] . DIRECTORY_SEPARATOR . $fileInfo['basename'];
                    };
                    break;
                case 'localpath':
                    // The key will be the dirname + basename - the search path ie. test/uploadForm.php
                    $this->keyClosure = function ($fileInfo) {
                        return substr($fileInfo['dirname'] . DIRECTORY_SEPARATOR . $fileInfo['basename'], strlen($fileInfo['searchpath']) + 1);
                    };
                    break;
                case 'apppath':
                    // The key will be the dirname + basename - the search path ie. /application/welcome/views/test/uploadForm.php
                    $this->keyClosure = function ($fileInfo) {
                        return substr($fileInfo['dirname'] . DIRECTORY_SEPARATOR . $fileInfo['basename'], strlen(__ROOT__));
                    };
                    break;
                case 'view':
                default:
                    // The key will be the dirname + basename - the search path - the extension ie. test/uploadForm
                    $this->keyClosure = function ($fileInfo) {
                        return substr($fileInfo['dirname'] . DIRECTORY_SEPARATOR . $fileInfo['basename'], strlen($fileInfo['searchpath']) + 1, -strlen($fileInfo['extension']) - 1);
                    };
                    break;
            }
        }
    }

    /**
     * add new directory
     * default to prepend into the array (add to the front of the array)
     *
     * @param string $directory
     * @param int|null $pend
     * @return DirectorySearch
     * @throws ClassLocked
     * @throws NotFound
     * @throws DirectoryNotFound
     */
    public function addDirectory(string $directory, int $pend = null): self
    {
        // should we throw an exception?
        $this->ifLockedThrowException();

        $pend = $pend ?? $this->pend;

        if ($found = realpath(rtrim($directory, DIRECTORY_SEPARATOR))) {
            if ($pend == self::PREPEND) {
                $this->directories = [$found => null] + $this->directories;
            } else {
                $this->directories[$found] = null;
            }

            // force a rescan on next read
            $this->rescan();
            $this->callback('addDirectory');
        } elseif (!$this->quiet) {
            throw new DirectoryNotFound($directory);
        }

        return $this;
    }

    /**
     * add new directories
     * use the 3rd argument 'asBlock' to keep the directories array in order when adding
     *
     * @param array $directories
     * @param int $prepend
     * @param bool $asBlock
     * @return DirectorySearch
     * @throws ClassLocked
     * @throws NotFound
     * @throws DirectoryNotFound
     */
    public function addDirectories(array $directories, int $pend = null, bool $asBlock = true): self
    {
        $pend = $pend ?? $this->pend;

        // pre pend as a block in this exact order
        if ($pend == self::PREPEND && $asBlock) {
            $directories = array_reverse($directories);
        }

        foreach ($directories as $directory) {
            $this->addDirectory($directory, $pend);
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

        $this->callback('removeDirectory');

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
        $this->callback('replaceDirectories');

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

        $this->callback('flushDirectories');

        return $this;
    }

    /* resources */

    public function addResource(string $resource, string $path): self
    {
        // should we throw an exception?
        $this->ifLockedThrowException();

        if ($path = realpath($path)) {
            // there may actually be multiple matching resources for 1 resource key
            $this->resources[$this->normalizeKey($resource)][$path] = null;
        }

        $this->callback('addResource');

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
        // should we throw an exception?
        $this->ifLockedThrowException();

        return $this->flushResources()->addResources($resources);
    }

    public function flushResources(): self
    {
        $this->resources = [];

        $this->callback('flushResources');

        return $this;
    }

    public function removeResource(string $resource): self
    {
        // should we throw an exception?
        $this->ifLockedThrowException();

        unset($this->resources[$this->normalizeKey($resource)]);

        return $this;
    }

    public function removeResources(array $resources): self
    {
        foreach ($resources as $key) {
            $this->removeResource($key);
        }

        return $this;
    }

    /**
     * find all matching
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

    /**
     * get a list of all resources
     *
     * @return array
     * @throws NotFound
     */
    public function list(): array
    {
        $this->scanDirectories();

        return array_keys($this->resources);
    }

    /**
     * Find the first matching resource
     *
     * @param string $resource
     * @return string
     * @throws NotFound
     * @throws ResourceNotFound
     */
    public function findFirst(string $resource): string
    {
        $found = $this->find($resource);

        return $found[array_key_first($found)] ?? '';
    }

    /**
     * Find the last matching resource
     *
     * @param string $resource
     * @return string
     * @throws NotFound
     * @throws ResourceNotFound
     */
    public function findLast(string $resource): string
    {
        $found = $this->find($resource);

        return $found[array_key_last($found)] ?? '';
    }

    /**
     * Does this resource exist in any directory?
     *
     * @param string $resource
     * @return bool
     */
    public function exists(string $resource): bool
    {
        $this->scanDirectories();

        return array_key_exists($this->normalizeKey($resource), $this->resources);
    }

    /**
     * lock the class from further modification
     *
     * @return DirectorySearch
     */
    public function lock(): self
    {
        $this->locked = true;

        return $this;
    }

    /**
     * unlock the class
     * This does not check it simple unlocks it
     *
     * @return DirectorySearch
     */
    public function unlock(): self
    {
        $this->locked = false;

        return $this;
    }

    /**
     * get if the class is locked
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * output sent when var_dump is used on this class
     *
     * @return array{resources: array, directories: array}
     */
    public function __debugInfo()
    {
        return ['resources' => $this->resources, 'directories' => $this->directories];
    }

    /**
     * Protected
     */

    /**
     * Scan all of the directorys for matching resources
     *
     * @return void
     * @throws NotFound
     */
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

    /**
     * add the matching resources
     *
     * @param string $searchPath
     * @param array|false $matches
     * @return void
     * @throws NotFound
     */
    protected function addMatches(string $searchPath, array|false $matches): void
    {
        if (is_array($matches)) {
            foreach ($matches as $file) {
                $fileInfo = pathinfo($file);
                $fileInfo['searchpath'] = $searchPath;
                $closureFunction = $this->keyClosure;
                // extract the key based on the function you chose
                $key = $closureFunction($fileInfo);
                // now add the resource
                $this->addResource($key, $file);
            }
        }
    }

    /**
     * If the directory search locks after the first scan
     * and they then try to change it we need to throw an exception
     *
     * @return void
     * @throws ClassLocked
     */
    protected function ifLockedThrowException(): void
    {
        if ($this->locked) {
            throw new ClassLocked(__CLASS__);
        }
    }

    /**
     * normalize the resource key
     *
     * @param string $key
     * @return string
     */
    protected function normalizeKey(string $key): string
    {
        $newKey = ($this->normalizeKeys) ? mb_convert_case($key, MB_CASE_LOWER, mb_detect_encoding($key)) : $key;

        return $this->hashKeys ? sha1($newKey, false) : $newKey;
    }

    /**
     *  Trigger a rescan on next read
     *
     * @return DirectorySearch
     */
    protected function rescan(): self
    {
        $this->rescan = true;

        return $this;
    }

    /**
     * register an additional callback function which
     * is called After most of the public functions
     *
     * @param string $action
     * @return DirectorySearch
     * @throws NotFound
     */
    protected function callback(string $action): self
    {
        // is a callback registered?
        if (!empty($this->callback)) {
            if (!is_object($this->callback[0]) || !method_exists($this->callback[0], $this->callback[1])) {
                throw new NotFound('Could not call Directory Search Callback');
            }

            // call the callback and pass the action and this object
            call_user_func($this->callback, [$action, $this]);
        }

        return $this;
    }
}
