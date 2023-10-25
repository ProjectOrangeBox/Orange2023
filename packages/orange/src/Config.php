<?php

declare(strict_types=1);

namespace dmyers\orange;

use ArrayObject;
use dmyers\orange\exceptions\ConfigFileNotFound;
use dmyers\orange\interfaces\ConfigInterface;
use dmyers\orange\exceptions\InvalidConfigurationValue;

class Config extends ArrayObject implements ConfigInterface
{
    private static ConfigInterface $instance;
    protected array $config = [];
    protected array $storage = [];
    protected array $searchPaths = [];
    protected bool $throwErrorOnMissingFile = false;

    public function __construct(array $config)
    {
        $this->config = $config;

        // Every matching config file array is merged over the last using "array_replace_recursive"
        // "Replaces elements from passed arrays into the first array recursively"

        $this->throwErrorOnMissingFile = $this->config['throw error on missing file'] ?? $this->throwErrorOnMissingFile;

        // Setup the default config folder sent in
        if (isset($this->config['config folder'])) {
            // default folder
            $this->addPath($this->config['config folder']);

            // setup environmental config folder
            // this path is searched after the default and any matching config files
            // are merged over the defaults making the configuration array environmental specific
            if (isset($this->config['environment'])) {
                $this->addPath($this->config['config folder'] . '/' . $config['environment']);
            }
        }
    }

    public static function getInstance(array $config): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public function addPath(string $absolutePath, bool $prepend = false): self
    {
        if ($prepend) {
            // add to the begninng of the search array
            array_unshift($this->searchPaths, $absolutePath);
        } else {
            // add to the end of the search array
            $this->searchPaths[] = $absolutePath;
        }

        return $this;
    }

    public function addPaths(array $absolutePaths, bool $prepend = false): self
    {
        foreach ($absolutePaths as $absolutePath) {
            $this->addPath($absolutePath, $prepend);
        }

        return $this;
    }

    /* magic methods */

    /**
     * return entire config file array or empty if not found
     */
    public function __get(string $filename): array
    {
        $name = $this->normalizeName($filename);

        if (!isset($this->storage[$name])) {
            $this->storage[$name] = $this->include($filename);
        }

        return $this->storage[$name];
    }

    /**
     * set the entire config array
     *
     * this is Stateless!
     */
    public function __set(string $filename, array $array): void
    {
        $this->storage[$this->normalizeName($filename)] = $array;
    }

    public function get(string $filename, string $key = '__#NOVALUE#__', mixed $default = null): mixed
    {
        // get entire config file array
        $value = $this->__get($filename);

        // if key is no value return the entire array
        // else just the single matching key if it exists
        if ($key !== '__#NOVALUE#__') {
            $value = $value[$key] ?? $default;
        }

        return $value;
    }

    public function set(string $filename, mixed $key = '__#NOVALUE#__', mixed $value = '__#NOVALUE#__'): void
    {
        if ($value !== '__#NOVALUE#__') {
            // get the entire array
            $configArray = $this->__get($filename);

            // set the value;
            $configArray[$key] = $value;

            // now value is the entire array
            $value = $configArray;
        }

        $this->__set($filename, $value);
    }

    /**
     * if you are caching the entire config array on a production system for example
     * use this to inject the entire storage array
     */
    public function setStorage(array $storage): self
    {
        $this->storage = $storage;

        return $this;
    }

    /**
     * protected
     */
    protected function include(string $filename): array
    {
        $found = false;
        $config = [];

        // first load the system configuration files
        // the root config folder configuration files
        // and then the environmental folder configuration files
        // replacing each matching value over the last
        foreach ($this->searchPaths as $path) {
            $absolutePath = rtrim($path, '/') . '/' . trim($filename, '/') . '.php';

            if (\file_exists($absolutePath)) {
                $found = true;
                $loadedConfig = include $absolutePath;

                if (!is_array($loadedConfig)) {
                    throw new InvalidConfigurationValue('"' . str_replace(__ROOT__, '', $absolutePath) . '" did not return an array.');
                }

                // merge (with replace) over the last
                $config = array_replace_recursive($config, $loadedConfig);
            }
        }

        if (!$found && $this->throwErrorOnMissingFile) {
            throw new ConfigFileNotFound($filename);
        }

        return $config;
    }

    protected function normalizeName(string $name): string
    {
        return mb_convert_case($name, MB_CASE_LOWER, mb_detect_encoding($name));
    }
}
