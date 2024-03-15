<?php

declare(strict_types=1);

namespace orange\framework;

use ArrayObject;
use orange\framework\DirectorySearch;
use orange\framework\interfaces\ConfigInterface;
use orange\framework\exceptions\InvalidConfigurationValue;

class Config extends ArrayObject implements ConfigInterface
{
    private static ?ConfigInterface $instance = null;
    protected array $config = [];
    protected array $loaded = [];

    public DirectorySearch $configSearch;

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    protected function __construct(array $config)
    {
        $this->config = $config;

        $this->configSearch = new DirectorySearch([
            'extension' => '.php',
            'quiet' => false,
        ]);

        // Setup the default config folder sent in
        if (isset($this->config['config folder'])) {
            // default folder
            $this->configSearch->addDirectory($this->config['config folder']);

            // setup environmental config folder
            // this path is searched after the default and any matching config files
            // are merged over the defaults making the configuration array environmental specific
            if (defined('ENVIRONMENT')) {
                $this->configSearch->addDirectory($this->config['config folder'] . DIRECTORY_SEPARATOR . ENVIRONMENT);
            }
        }
    }

    public static function getInstance(array $config): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    /* magic methods */

    /**
     * return entire config file array or empty if not found
     */
    public function __get(string $filename): array
    {
        return $this->get($filename);
    }

    public function get(string $filename, string $key = null, mixed $default = null): mixed
    {
        // get entire config file array
        $value = $this->getFile($filename);

        // if key is no value return the entire array
        // else just the single matching key if it exists
        if ($key !== null) {
            $value = $value[$key] ?? $default;
        }

        return $value;
    }

    protected function search(string $filename): array
    {
        // track if we at least find one
        $config = [];

        // first load the system configuration files
        // the root config folder configuration files
        // and then the environmental folder configuration files
        // replacing each matching value over the last
        foreach ($this->configSearch->findAll($filename) as $absolutePath) {
            // for each found match replace previous
            $config = array_replace_recursive($config, $this->include($absolutePath));
        }

        return $config;
    }

    protected function include(string $absolutePath): array
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

    protected function getFile(string $filename): array
    {
        $name = $this->normalizeName($filename);

        // only try to load from storage if it's hasn't already been loaded
        if (!isset($this->loaded[$name])) {
            $this->loaded[$name] = $this->search($filename);
        }

        return $this->loaded[$name];
    }

    protected function normalizeName(string $name): string
    {
        return mb_convert_case($name, MB_CASE_LOWER, mb_detect_encoding($name));
    }
}
