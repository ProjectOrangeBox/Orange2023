<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\interfaces\ConfigInterface;
use orange\framework\exceptions\filesystem\DirectoryNotFound;
use orange\framework\exceptions\config\InvalidConfigurationValue;

class Config extends SingletonArrayObject implements ConfigInterface
{
    protected array $searchPaths = [];

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    protected function __construct(array $config)
    {
        $this->build($config);

        // Exchange my array for another one
        $this->exchangeArray($this->data);
    }

    /**
     * return entire config file array or empty if not found
     */
    public function __get(string $filename): array
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

    /* protected */
    protected function build($config): void
    {
        // Setup the default config folder sent in
        if (isset($config['config folder'])) {
            // default folder
            if ($configFolder = realpath($config['config folder'])) {
                $this->searchPaths[] = $configFolder;
            } else {
                // default config folder is required
                throw new DirectoryNotFound($config['config folder']);
            }

            // setup environmental config folder
            // this path is searched after the default and any matching config files
            // are merged over the defaults making the configuration array environmental specific
            if (defined('ENVIRONMENT') && $envFolder = realpath($config['config folder'] . DIRECTORY_SEPARATOR . ENVIRONMENT)) {
                $this->searchPaths[] = $envFolder;
            }

            // technically it is already in $this->data
            $this->data = $this->scanAll();
        }
    }

    protected function load(string $filename): array
    {
        $key = strtolower($filename);

        // only try to load from storage if it's hasn't already been loaded
        if (!isset($this->data[$key])) {
            $this->data[$key] = $this->includeAll($filename);
        }

        return $this->data[$key];
    }

    protected function includeAll(string $filename): array
    {
        // track if we at least find one
        $config = [];

        // first load the system configuration files
        // the root config folder configuration files
        // and then the environmental folder configuration files
        // replacing each matching value over the last
        foreach ($this->searchPaths as $absolutePath) {
            $filePath = $absolutePath . DIRECTORY_SEPARATOR . $filename . '.php';

            if (file_exists($filePath)) {
                // for each found match replace previous
                $config = array_replace_recursive($config, $this->includeOne($filePath));
            }
        }

        return $config;
    }

    protected function includeOne(string $absolutePath): array
    {
        // bring the config file into local scope
        $loadedConfig = include $absolutePath;

        // must be an array returned
        if (!is_array($loadedConfig)) {
            throw new InvalidConfigurationValue('"' . str_replace(__ROOT__, '', $absolutePath) . '" did not return an array.');
        }

        // Every matching config file array is merged over the last using "array_replace_recursive"
        // "Replaces elements from passed arrays into the first array recursively"
        return $loadedConfig;
    }

    protected function scanAll(): array
    {
        foreach ($this->getAllFileNames() as $filename) {
            $this->load($filename);
        }

        return $this->data;
    }

    protected function getAllFileNames(): array
    {
        $filenames = [];

        foreach ($this->searchPaths as $path) {
            foreach (glob($path . '/*.php') as $file) {
                $filename = basename($file, '.php');

                $filenames[$filename] = $filename;
            }
        }

        return $filenames;
    }
}
