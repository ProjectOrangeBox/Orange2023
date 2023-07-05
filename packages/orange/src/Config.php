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
    protected string $unset = '__#UNSET#__';

    private function __construct(array $config)
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

    public function __get(string $filename): mixed
    {
        $value = [];

        $name = $this->normalizeName($filename);

        if (isset($this->storage[$name]) && $this->storage[$name] != $this->unset) {
            $value = $this->storage[$name];
        } elseif (!isset($this->storage[$name])) {
            $value = $this->storage[$name] = $this->include($filename);
        }

        return $value;
    }

    public function __set(string $filename, mixed $value): void
    {
        $this->storage[$this->normalizeName($filename)] = $value;
    }

    /* regular methods */

    public function get(string $filename): mixed
    {
        return $this->__get($filename);
    }

    public function set(string $filename, mixed $value): void
    {
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
