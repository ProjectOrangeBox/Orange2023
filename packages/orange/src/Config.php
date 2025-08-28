<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\base\SingletonArrayObject;
use orange\framework\interfaces\CacheInterface;
use orange\framework\interfaces\ConfigInterface;
use orange\framework\exceptions\config\ConfigNotFound;
use orange\framework\exceptions\filesystem\DirectoryNotFound;
use orange\framework\exceptions\config\InvalidConfigurationValue;
use orange\framework\exceptions\config\ConfigFileDidNotReturnAnArray;

/**
 * Overview of Config.php
 *
 * This file defines the Config class in the orange\framework namespace.
 * It is the central configuration manager for the framework, responsible for loading,
 * merging, and serving configuration files in a structured way.
 * It follows the Singleton pattern, ensuring there is only one configuration instance shared across the application.
 *
 * ⸻
 *
 * 1. Core Purpose
 * 	•	Provides a unified way to load configuration files.
 * 	•	Supports multiple directories (with priority order).
 * 	•	Allows environment-specific overrides.
 * 	•	Implements caching of config metadata for performance.
 * 	•	Gives developers array-style access (via ArrayObject) as well as method access.
 *
 * ⸻
 *
 * 2. Key Properties
 * 	•	$configuration → stores loaded configurations indexed by filename.
 * 	•	$searchDirectories → list of directories where configuration files will be searched.
 * 	•	$foundDirectoriesByName → map of config file names to their discovered file paths across directories.
 *
 * ⸻
 *
 * 3. Initialization
 * 	•	Constructor is protected (Singleton enforced).
 * 	•	Accepts:
 * 	•	$config → array of directories to search.
 * 	•	$cacheService → optional cache service implementing CacheInterface.
 * 	•	If caching is enabled:
 * 	•	Tries to load cached map of config files.
 * 	•	If cache is missing, builds the map and stores it.
 * 	•	If no cache service, always builds the map fresh.
 *
 * ⸻
 *
 * 4. Configuration Loading
 * 	•	load($filename)
 * 	•	Finds all files with that name (e.g., database.php) across directories.
 * 	•	Includes them, ensuring each returns an array.
 * 	•	Merges them using array_replace_recursive() (later directories override earlier ones).
 * 	•	Stores result in $configuration[$filename].
 * 	•	Error handling
 * 	•	Throws ConfigFileDidNotReturnAnArray if included file does not return an array.
 *
 * ⸻
 *
 * 5. Access Methods
 * 	•	Magic getter (__get) → $config->database fetches the whole database.php array.
 * 	•	offsetExists() → array-style access to checks if a config file exists.
 * 	•	offsetGet() → array-style access to config file ($config['database']).
 * 	•	get($filename, $key = null, $default = null)
 * 	•	Fetches entire config file (if $key is null).
 * 	•	Fetches specific key with fallback default.
 *
 * ⸻
 *
 * 6. Support Methods
 * 	•	buildArray() → scans all search directories for *.php config files and builds an index.
 * 	•	Uses glob() to find files.
 * 	•	Returns array like:
 * 	     [
 *       'database' => ['/path/to/config/database.php', '/path/to/env/database.php'],
 *       'app' => ['/path/to/config/app.php']
 * 	     ]
 *
 * 7. Big Picture
 * 	•	Config.php is the backbone for configuration management in the framework.
 * 	•	It ensures configs are:
 * 	•	Centralized
 * 	•	Overridable by environment
 * 	•	Efficiently merged and cached
 * 	•	Provides flexible access ($config->file, $config['file'], $config->get('file','key')).
 *
 * @package orange\framework
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

    protected array $foundDirectoriesByName = [];

    /**
     * Protected constructor to enforce Singleton usage.
     *
     * @param array $config Initial configuration array.
     * @throws DirectoryNotFound If the default configuration directory is invalid.
     */
    protected function __construct(array $config = [], ?CacheInterface $cacheService = null)
    {
        logMsg('INFO', __METHOD__);

        $this->searchDirectories = $config;

        if ($cacheService) {
            // cache key
            $cacheKey = ENVIRONMENT . '\\' . __CLASS__;

            if ($cached = $cacheService->get($cacheKey)) {
                $this->foundDirectoriesByName = $cached;
            } else {
                $cacheService->set($cacheKey, $this->foundDirectoriesByName = $this->buildArray());
            }
        } else {
            $this->foundDirectoriesByName = $this->buildArray();
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

        $this->load($filename);

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
        $completeConfig = $this->load($filename);

        // Return the entire array if no key is specified
        return $key !== null ? ($completeConfig[$key] ?? $defaultValue) : $completeConfig;
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
        $config = [];

        // Check if the configuration file exists in the found directories
        if (isset($this->foundDirectoriesByName[$filename])) {
            // Check if configuration has already been loaded
            if (!isset($this->configuration[$filename])) {
                $foundConfigs = [];

                foreach ($this->foundDirectoriesByName[$filename] as $configFile) {
                    if (!is_array($includedConfig = include $configFile)) {
                        throw new ConfigFileDidNotReturnAnArray('"' . $configFile . '" did not return an array.');
                    }

                    $foundConfigs[] = $includedConfig;
                }

                // now let's do the merge all at once.
                $this->configuration[$filename] = array_replace_recursive(...$foundConfigs);
            }

            $config = $this->configuration[$filename];
        }

        // and now configuration has the configuration array
        return $config;
    }

    /**
     * Find configuration files by name across search directories.
     * In production this can be cached.
     *
     * @return array
     */
    protected function buildArray(): array
    {
        $found = [];

        // find all of the cache file names by reading all of the searchDirectories
        foreach ($this->searchDirectories as $searchDirectory) {
            foreach (glob($searchDirectory . DIRECTORY_SEPARATOR . '*.php') as $file) {
                $name = basename($file, '.php');

                if (!isset($found[$name])) {
                    $found[$name] = [];
                }

                $found[$name][] = realpath($file);
            }
        }

        return $found;
    }
}
