<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\base\SingletonArrayObject;
use orange\framework\interfaces\ConfigInterface;
use orange\framework\exceptions\filesystem\DirectoryNotFound;
use orange\framework\exceptions\config\InvalidConfigurationValue;

class Config extends SingletonArrayObject implements ConfigInterface
{
    // configuration key value for the configuration folder
    protected string $configKey = 'config folder';
    // array of configs found merged based on the filename
    protected array $configuration = [];
    // paths to search for config files in order of seaerch
    protected array $searchPaths = [];
    // config files found for a given filename
    protected array $foundConfigFiles = [];

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    protected function __construct(array $config)
    {
        // Setup the default config folder sent in
        if (isset($config[$this->configKey])) {
            // default folder
            if ($configFolder = realpath($config[$this->configKey])) {
                $this->searchPaths[] = $configFolder;
            } else {
                // default config folder is required
                throw new DirectoryNotFound($config[$this->configKey]);
            }

            // setup environmental config folder
            // this path is searched after the default and any matching config files
            // are merged over the defaults making the configuration array environmental specific
            if (defined('ENVIRONMENT') && $envFolder = realpath($config[$this->configKey] . DIRECTORY_SEPARATOR . ENVIRONMENT)) {
                $this->searchPaths[] = $envFolder;
            }
        }
    }

    /**
     * return entire config file array or empty if not found
     */
    public function __get(string $filename): mixed
    {
        return $this->get($filename);
    }

    /**
     * support for ArrayObject
     *
     * @param mixed $filename
     * @return bool
     */
    public function offsetExists(mixed $filename): bool
    {
        $this->findConfigFiles($filename);

        return count($this->foundConfigFiles[$filename]) > 0;
    }

    /**
     * support for ArrayObject
     *
     * @param mixed $filename
     * @return mixed|null
     * @throws InvalidConfigurationValue
     */
    public function offsetGet(mixed $filename): mixed
    {
        return $this->get($filename);
    }

    public function get(string $filename, string $key = null, mixed $defaultValue = null): mixed
    {
        // get entire config file array
        $value = $this->load($filename);

        // if key is no value return the entire array
        // else just the single matching key if it exists
        if ($key !== null) {
            $value = $value[$key] ?? $defaultValue;
        }

        return $value;
    }

    /**
     *
     * Protected
     *
     **/
    protected function load(string $filename): array
    {
        // only try to load from storage if it's hasn't already been loaded
        if (!isset($this->configuration[$filename])) {
            $this->configuration[$filename] = [];

            $this->findConfigFiles($filename);

            // first iclude the config folder configuration file
            // then the environmental config folder configuration file
            // replacing each matching value over the last
            foreach ($this->foundConfigFiles[$filename] as $absolutePath) {
                $this->configuration[$filename] = array_replace_recursive($this->configuration[$filename], $this->include($absolutePath));
            }
        }

        return $this->configuration[$filename];
    }

    protected function include(string $absolutePath): array
    {
        // bring the config file into local scope
        $configuration = include $absolutePath;

        // must be an array returned
        if (!is_array($configuration)) {
            throw new InvalidConfigurationValue('"' . str_replace(__ROOT__, '', $absolutePath) . '" did not return an array.');
        }

        // Every matching config file array is merged over the last using "array_replace_recursive"
        // "Replaces elements from passed arrays into the first array recursively"
        return $configuration;
    }

    protected function findConfigFiles(string $filename): void
    {
        // only try to search for config files if it hasn't already been searched
        if (!isset($this->foundConfigFiles[$filename])) {
            // set to empty array of "no matches found"
            $this->foundConfigFiles[$filename] = [];

            foreach ($this->searchPaths as $searchPath) {
                $absolutePath = $searchPath . DIRECTORY_SEPARATOR . $filename . '.php';

                if (file_exists($absolutePath)) {
                    $this->foundConfigFiles[$filename][] = $absolutePath;
                }
            }
        }
    }
}
