<?php

declare(strict_types=1);

namespace dmyers\orange;

use ArrayObject;
use dmyers\orange\interfaces\ConfigInterface;
use dmyers\orange\exceptions\InvalidConfigurationValue;

class Config extends ArrayObject implements ConfigInterface
{
    private static ConfigInterface $instance;
    protected array $storage = [];
    protected array $searchPaths = [];

    public function __construct(array $config)
    {
        include_once __DIR__ . '/ConfigHelper.php';

        if (isset($config['skip defaults']) && $config['skip defaults'] != true) {
            // orange default configurations folder
            $this->searchPaths[] = __DIR__ . '/config';
        }

        if (isset($config['config folder'])) {
            // default folder
            $this->searchPaths[] = $config['config folder'];
        }

        if (isset($config['environment'])) {
            // add the environmental folders (loaded last over the others)
            $this->searchPaths[] = $config['config folder'] . '/' . $config['environment'];
        }
    }

    public static function getInstance(array $searchPaths): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($searchPaths);
        }

        return self::$instance;
    }

    public function addPath(string $absPath, bool $prepend = false): self
    {
        if ($prepend) {
            array_unshift($this->searchPaths, $absPath);
        } else {
            $this->searchPaths[] = $absPath;
        }

        return $this;
    }

    /* magic methods */

    public function __get(string $filename): array
    {
        $name = $this->normalizeName($filename);

        if (!isset($this->storage[$name])) {
            $this->storage[$name] = $this->include($filename);
        }

        return $this->storage[$name];
    }

    public function __set(string $filename, array $value): void
    {
        $this->storage[$this->normalizeName($filename)] = $value;
    }

    public function get(string $filename, mixed $key = NOVALUE): mixed
    {
        $value = $this->__get($filename);
        
        if ($key !== NOVALUE) {
            $value = isset($value[$key]) ? $value[$key] : null;
        }
        
        return $value;
    }

    public function set(string $filename, mixed $key, mixed $value = NOVALUE): void
    {
        if ($value !== NOVALUE) {
            $config = $this->__get($filename);

            $config[$key] = $value;

            $value = $config;
        }

        $this->__set($filename, $value);
    }

    /**
     * protected
     */
    protected function include(string $filename): array
    {
        $config = [];

        // first load the system configuration files
        // the root config folder configuration files
        // and then the environmental folder configuration files
        // replacing each matching value over the last
        foreach ($this->searchPaths as $path) {
            $absFilePath = rtrim($path, '/') . '/' . trim($filename, '/') . '.php';

            if (\file_exists($absFilePath)) {
                $loadedConfig = include $absFilePath;

                if (!is_array($loadedConfig)) {
                    throw new InvalidConfigurationValue('"' . $absFilePath . '" did not return an array.');
                }

                $config = array_replace_recursive($config, $loadedConfig);
            }
        }

        return $config;
    }

    protected function normalizeName(string $name): string
    {
        return mb_convert_case($name, MB_CASE_LOWER, mb_detect_encoding($name));
    }

    public function __debugInfo(): array
    {
        return [
            'storage' => $this->storage,
            'searchPaths' => $this->searchPaths
        ];
    }
}
