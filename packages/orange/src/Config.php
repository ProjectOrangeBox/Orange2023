<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\base\SingletonArrayObject;
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
     * @var array $configuration
     * Stores loaded configurations indexed by filename.
     */
    protected array $configuration = [];

    /**
     * @var array $searchPaths
     * Array of directories to search for configuration files, in order of priority.
     */
    protected array $searchPaths = [];

    /**
     * @var array $foundConfigFiles
     * Maps configuration filenames to their absolute file paths.
     */
    protected array $foundConfigFiles = [];

    /**
     * Protected constructor to enforce Singleton usage.
     *
     * @param array $config Initial configuration array.
     * @throws DirectoryNotFound If the default configuration directory is invalid.
     */
    protected function __construct(array $config)
    {
        logMsg('INFO', __METHOD__);

        // Setup the default configuration directory
        if (isset($config['config directory'])) {
            // Validate and add the default configuration directory
            if ($configDirectory = realpath($config['config directory'])) {
                $this->searchPaths[] = $configDirectory;
            } else {
                throw new DirectoryNotFound($config['config directory']);
            }

            // Setup environment-specific configuration directory
            if (!($config['skip env'] ?? false)) {
                if (defined('ENVIRONMENT') && $envDirectory = realpath($config['config directory'] . DIRECTORY_SEPARATOR . ENVIRONMENT)) {
                    $this->searchPaths[] = $envDirectory;
                }
            }
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
    public function get(string $filename, string $key = null, mixed $defaultValue = null): mixed
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
            $this->configuration[$filename] = [];

            $this->findConfigFiles($filename);

            // Merge configurations from multiple found files
            foreach ($this->foundConfigFiles[$filename] as $absolutePath) {
                $this->configuration[$filename] = array_replace_recursive(
                    $this->configuration[$filename],
                    $this->include($absolutePath)
                );
            }
        }

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
            logMsg('INFO', 'INCLUDE FILE "' . $absolutePath . '"');

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
     */
    protected function findConfigFiles(string $filename): void
    {
        logMsg('INFO', __METHOD__ . ' ' . $filename);

        if (!isset($this->foundConfigFiles[$filename])) {
            $this->foundConfigFiles[$filename] = [];

            // Search through each directory for the configuration file
            foreach ($this->searchPaths as $searchPath) {
                $absolutePath = $searchPath . DIRECTORY_SEPARATOR . $filename . '.php';

                if (file_exists($absolutePath)) {
                    $this->foundConfigFiles[$filename][] = $absolutePath;
                }
            }
        }
    }
}
