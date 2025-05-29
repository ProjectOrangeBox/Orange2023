<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\base\SingletonArrayObject;
use orange\framework\interfaces\CacheInterface;
use orange\framework\interfaces\ConfigInterface;
use orange\framework\exceptions\filesystem\DirectoryNotFound;
use orange\framework\exceptions\config\InvalidConfigurationValue;

/**
 * Class Config
 *
 * Manages configuration files in a structured and hierarchical manner.
 * Implements the Singleton pattern to ensure a single instance manages configurations.
 * Supports configuration merging across multiple environments and directories.
 *
 * Features:
 * - Dynamic configuration loading
 * - Environment-specific configurations
 * - Support for array-based access via ArrayObject
 * - Configuration caching for efficiency
 */
class Config extends SingletonArrayObject implements ConfigInterface
{
    /**
     * Stores loaded configurations indexed by filename.
     */
    protected array $configuration = [];

    /**
     * Array of directories to search for configuration files, in order of priority.
     */
    protected array $searchDirectories = [];

    /**
     * Maps configuration filenames to their absolute file paths.
     */
    protected array $foundConfigFiles = [];

    /**
     * Protected constructor to enforce Singleton usage.
     *
     * @param array $config Initial configuration array.
     * @throws DirectoryNotFound If the default configuration directory is invalid.
     */
    protected function __construct(array $config, ?CacheInterface $cache = null)
    {
        logMsg('INFO', __METHOD__);

        $this->searchDirectories = $config['search directories'] ?? [];

        if ($cache) {
            $this->loadCache($cache);
        }
    }

    /**
     * Magic getter to retrieve configuration for a specific filename.
     *
     * @param string $filename Name of the configuration file (without extension).
     * @return mixed Configuration data or null if not found.
     */
    public function __get(string $filename): mixed
    {
        return $this->get($filename);
    }

    /**
     * Check if a configuration file exists.
     *
     * @param mixed $filename Name of the configuration file.
     * @return bool True if the configuration file exists, false otherwise.
     */
    public function offsetExists(mixed $filename): bool
    {
        logMsg('INFO', __METHOD__ . ' ' . $filename);

        $this->findConfigFiles($filename);

        return count($this->foundConfigFiles[$filename]) > 0;
    }

    /**
     * Retrieve configuration content for a specific file.
     *
     * @param mixed $filename Name of the configuration file.
     * @return mixed Configuration data.
     * @throws InvalidConfigurationValue If configuration data is invalid.
     */
    public function offsetGet(mixed $filename): mixed
    {
        logMsg('INFO', __METHOD__ . ' ' . $filename);

        return $this->get($filename);
    }

    /**
     * Retrieve configuration data by filename and optional key.
     *
     * @param string $filename Name of the configuration file.
     * @param string|null $key Specific key within the configuration file.
     * @param mixed $defaultValue Default value if the key does not exist.
     * @return mixed Configuration value or default value if key not found.
     */
    public function get(string $filename, ?string $key = null, mixed $defaultValue = null): mixed
    {
        logMsg('INFO', __METHOD__ . ' ' . $filename . '.' . ($key ?? '*'));

        // Load the configuration file
        $value = $this->load($filename);

        // Return the entire array if no key is specified
        return $key !== null ? ($value[$key] ?? $defaultValue) : $value;
    }

    /**
     * Load a configuration file into memory.
     *
     * @param string $filename Name of the configuration file.
     * @return array The configuration array.
     * @throws InvalidConfigurationValue If the configuration file doesn't return an array.
     */
    protected function load(string $filename): array
    {
        // Check if configuration has already been loaded
        if (!isset($this->configuration[$filename])) {
            // it has not so let's start with an empty configuration array
            $this->configuration[$filename] = [];

            // find all the config files matching this filename
            $this->findConfigFiles($filename);

            // merge configurations from multiple found files
            foreach ($this->foundConfigFiles[$filename] as $absolutePath) {
                $this->configuration[$filename] = array_replace_recursive(
                    $this->configuration[$filename],
                    $this->include($absolutePath)
                );
            }
        }

        // and now configuration has the configuration array
        return $this->configuration[$filename];
    }

    /**
     * Include and parse a configuration file.
     *
     * @param string $absolutePath Absolute path to the configuration file.
     * @return array Parsed configuration array.
     * @throws InvalidConfigurationValue If the included file does not return an array.
     */
    protected function include(string $absolutePath): array
    {
        logMsg('INFO', __METHOD__);

        if (!isset($this->foundConfigFiles[$absolutePath])) {
            logMsg('INFO', 'Include File: "' . $absolutePath . '"');

            $this->foundConfigFiles[$absolutePath] = include $absolutePath;

            if (!is_array($this->foundConfigFiles[$absolutePath])) {
                throw new InvalidConfigurationValue('"' . str_replace(__ROOT__, '', $absolutePath) . '" did not return an array.');
            }
        }

        return $this->foundConfigFiles[$absolutePath];
    }

    /**
     * Search for configuration files across all defined paths.
     *
     * @param string $filename Name of the configuration file.
      * @return void 
     */
    protected function findConfigFiles(string $filename): void
    {
        logMsg('INFO', __METHOD__ . ' ' . $filename);

        // did we do a search for this config filename
        if (!isset($this->foundConfigFiles[$filename])) {
            // nope so we need to start with an empty configuration array
            $this->foundConfigFiles[$filename] = [];

            // Search through each directory for the configuration file
            foreach ($this->searchDirectories as $searchDirectory) {
                if ($absolutePath = realpath($searchDirectory . DIRECTORY_SEPARATOR . $filename . '.php')) {
                    $this->foundConfigFiles[$filename][] = $absolutePath;
                }
            }
        }
    }

    /**
     * Load configuration & configuration arrays from the arrays
     * 
     * @param CacheInterface $cache 
     * @return void 
     * @throws InvalidConfigurationValue 
     */
    protected function loadCache(CacheInterface $cache): void
    {
        logMsg('INFO', __METHOD__);

        // cache key
        $key = hash('sha256', var_export($this->searchDirectories, true) . '::' . __METHOD__);

        $cached = $cache->get($key);

        // has anything already been cached?
        if (!$cached) {
            // no
            // find all of the cache file names by reading all of the searchDirectories
            foreach ($this->searchDirectories as $searchDirectory) {
                foreach (glob($searchDirectory . '/*.php') as $file) {
                    // trigger a read on all of them
                    $this->__get(basename($file, '.php'));

                    // cache the results
                    $cache->set($key, ['configuration' => $this->configuration, 'foundConfigFiles' => $this->foundConfigFiles]);
                }
            }
        } else {
            // yes
            // load it and setup the correct properties
            $this->configuration = $cached['configuration'];
            $this->configuration = $cached['foundConfigFiles'];
        }
    }
}
