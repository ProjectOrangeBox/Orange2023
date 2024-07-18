<?php

declare(strict_types=1);

namespace orange\framework;

use ArrayObject;
use orange\framework\DirectorySearch;
use orange\framework\interfaces\ConfigInterface;
use orange\framework\exceptions\filesystem\DirectoryNotFound;
use orange\framework\exceptions\config\InvalidConfigurationValue;

class Config extends ArrayObject implements ConfigInterface
{
    private static ?ConfigInterface $instance;

    protected array $loaded;

    // pass thru
    public DirectorySearch $search;

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    protected function __construct(array $config)
    {
        $this->search = new DirectorySearch([
            'match' => '*.php',
            // do NOT throw error if the config file is not found
            'quiet' => true,
            'lock after scan' => true,
            'normalize keys' => true,
            'resource key style' => 'config',
            'prepend' => true,
            'callback' => [$this, 'flush'],
        ]);

        // nothing loaded
        $this->loaded = [];

        // Setup the default config folder sent in
        if (isset($config['config folder'])) {
            // default folder
            if ($configFolder = $config['config folder']) {
                $this->search->addDirectory($configFolder, DirectorySearch::FIRST);
            } else {
                // default config folder is required
                throw new DirectoryNotFound($config['config folder']);
            }

            // setup environmental config folder
            // this path is searched after the default and any matching config files
            // are merged over the defaults making the configuration array environmental specific
            if (defined('ENVIRONMENT') && $envFolder = realpath($config['config folder'] . DIRECTORY_SEPARATOR . ENVIRONMENT)) {
                $this->search->addDirectory($envFolder, DirectorySearch::LAST);
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

    public function flush(): void
    {
        $this->loaded = [];
    }

    /* magic methods */

    /**
     * return entire config file array or empty if not found
     */
    public function __get(string $filename): array
    {
        return $this->get($filename);
    }

    public function get(string $filename, string $arrayKey = null, mixed $defaultValue = null): mixed
    {
        // get entire config file array
        $value = $this->load($filename);

        // if key is no value return the entire array
        // else just the single matching key if it exists
        if ($arrayKey !== null) {
            $value = $value[$arrayKey] ?? $defaultValue;
        }

        return $value;
    }

    protected function load(string $filename): array
    {
        $name = strtolower($filename);

        // only try to load from storage if it's hasn't already been loaded
        if (!isset($this->loaded[$name])) {
            $this->loaded[$name] = $this->includeAll($filename);
        }

        return $this->loaded[$name];
    }

    protected function includeAll(string $filename): array
    {
        // track if we at least find one
        $config = [];

        // first load the system configuration files
        // the root config folder configuration files
        // and then the environmental folder configuration files
        // replacing each matching value over the last
        foreach ($this->search->find($filename) as $absolutePath) {
            // for each found match replace previous
            $config = array_replace_recursive($config, $this->includeOne($absolutePath));
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
}
