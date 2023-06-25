<?php

declare(strict_types=1);

namespace dmyers\orange;

use ArrayObject;
use dmyers\orange\interfaces\ConfigInterface;
use dmyers\orange\exceptions\InvalidConfigurationValue;
use dmyers\orange\exceptions\InvalidValue;

class Config extends ArrayObject implements ConfigInterface
{
    private static ConfigInterface $instance;
    protected array $storage = [];
    protected array $searchPaths = [];

    private function __construct(array $searchPaths)
    {
        include_once __DIR__ . '/ConfigHelper.php';

        $this->searchPaths = $searchPaths;

        // orange default configurations folder
        $this->addPath(__DIR__ . '/config', true);
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

    public function __get(string $filename): mixed
    {
        $key = $this->normalizeName($filename);

        if (!isset($this->storage[$key])) {
            $this->storage[$key] = $this->include($filename);
        }

        return $this->storage[$filename];
    }

    public function __set(string $filename, mixed $value): void
    {
        $this->set($filename, $value);
    }

    public function __unset(string $filename): void
    {
        unset($this->storage[$this->normalizeName($filename)]);
    }

    public function __isset(string $filename): bool
    {
        return isset($this[$this->normalizeName($filename)]);
    }

    public function get(string $filename): mixed
    {
        return $this->__get($filename);
    }

    public function isset(string $filename): bool
    {
        return $this->__isset($filename);
    }

    public function unset(string $filename): void
    {
        $this->__unset($filename);
    }

    public function set(string $filename, mixed $value): void
    {
        $this->storage[$filename] = $value;
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

    protected static function normalizeName(string $name): string
    {
        return mb_convert_case($name, MB_CASE_LOWER, mb_detect_encoding($name));
    }
}
