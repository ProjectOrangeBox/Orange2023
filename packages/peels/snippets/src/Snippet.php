<?php

use peels\cache\CacheInterface;

class Snippet
{
    // only load on request
    protected string $databaseTablename = 'snippets';
    protected string $filePath = 'snippets';
    protected array $mergeOrder = ['file', 'database', 'construct'];

    protected array $constructArray = [];
    protected array $databaseArray = [];
    protected array $fileArray = [];

    protected bool $databaseLoaded = false;
    protected bool $fileLoaded = false;
    protected PDO $pdo;
    protected CacheInterface $cache;

    private static Snippet $instance;

    public function __construct(array $config)
    {
        $this->mergeOrder = $config['merge order'] ?? $this->mergeOrder;
        $this->constructArray = $config['snippets'] ?? $this->constructArray;
        $this->filePath = $config['file path'] ?? $this->filePath;
        $this->databaseTablename = $config['database tablename'] ?? $this->databaseTablename;
        $this->pdo = $config['pdo'] ?? $this->pdo;
        $this->cache = $config['cache'] ?? $this->cache;
    }

    public static function getInstance(array $config, array $data = []): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new (get_called_class())($config, $data);
        }

        return self::$instance;
    }

    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    public function get(string $key, string $default = ''): string
    {
        $value = $default;

        foreach ($this->mergeOrder as $method) {
            $method = strtolower($method);

            // return true on match
            // value passed by reference
            if ($this->$method($key, $value)) {
                // break from loop
                break;
            }
        }

        return $value;
    }

    protected function database(string $key, string &$value): bool
    {
        if (!$this->databaseLoaded) {
            $cacheKey = __METHOD__ . '::database';

            if (!$this->getCache($cacheKey, $this->databaseArray)) {
                $this->databaseArray = $this->pdo->query("select name,value from `" . $this->databaseTablename . "` where is_active = 1")->fetchAll(PDO::FETCH_KEY_PAIR);
               
                $this->setCache($cacheKey, $this->databaseArray);
            }
        }

        return $this->pick($value, $key, $this->databaseArray);
    }

    protected function file(string $key, string &$value): bool
    {
        if (!$this->fileLoaded) {
            $cacheKey = __METHOD__ . '::file';

            if (!$this->getCache($cacheKey, $this->fileArray)) {
                $filePath = __ROOT__ . '/' . trim($this->filePath, '/') . '.php';

                if (!file_exists($filePath)) {
                    throw new Exception('Snippet config file "' . $filePath . '" not found.');
                }

                $fileArray = require $filePath;

                if (!is_array($fileArray)) {
                    throw new Exception('Snippet File didn\'t return an Array.');
                }

                $this->fileArray = $fileArray;

                $this->setCache($cacheKey, $this->fileArray);
            }
        }

        return $this->pick($value, $key, $this->fileArray);
    }

    protected function construct(string $key, string &$value): bool
    {
        return $this->pick($value, $key, $this->constructArray);
    }

    protected function pick(string &$value, string $key, array $search): bool
    {
        $found = false;

        if (isset($search[$key])) {
            $value = $search[$key];
            $found = true;
        }

        return $found;
    }

    protected function getCache(string $cacheKey, array &$array): bool
    {
        $return = false;

        if ($this->cache) {
            $array = $this->cache->get($cacheKey);
        }

        return $return;
    }

    protected function setCache(string $cacheKey, array $array): void
    {
        if ($this->cache) {
            $this->cache->set($cacheKey, $array);
        }
    }
}
