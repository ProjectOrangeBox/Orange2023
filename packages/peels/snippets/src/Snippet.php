<?php

declare(strict_types=1);

namespace peels\snippets;

use PDO;
use peels\cache\CacheInterface;
use peels\snippets\SnippetException;

class Snippet implements SnippetInterface
{
    // only load on request
    protected string $databaseTablename = 'snippets';
    protected string $filePath = 'snippets';
    protected array $mergeOrder = ['file', 'database', 'config'];

    protected array $databaseArray = [];
    protected array $fileArray = [];
    protected array $configArray = [];

    protected bool $databaseLoaded = false;
    protected bool $fileLoaded = false;

    protected ?PDO $pdo = null;
    protected ?CacheInterface $cache = null;

    protected mixed $plucked;

    protected static ?SnippetInterface $instance = null;

    public function __construct(array $config)
    {
        $this->mergeOrder = $config['merge order'] ?? $this->mergeOrder;

        $this->configArray = $config['snippets'] ?? $this->configArray;
        $this->filePath = $config['file path'] ?? $this->filePath;

        if (isset($config['pdo'])) {
            $this->pdo = $config['pdo'];
            $this->databaseTablename = $config['database tablename'] ?? $this->databaseTablename;
        }

        if (isset($config['cache'])) {
            $this->cache = $config['cache'];
        }
    }

    public static function getInstance(array $config): self
    {
        if (self::$instance === null) {
            self::$instance = new ($config);
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
            $method = 'pick' . ucfirst(strtolower($method));

            // return true on match
            // value passed by reference
            if ($this->$method($key, $value)) {
                $value = $this->plucked;
                // break from loop
                break;
            }
        }

        return $value;
    }

    /* protected */

    protected function pickDatabase(string $key, string $value): bool
    {
        if (!$this->databaseLoaded && $this->pdo !== null) {
            $cacheKey = __METHOD__ . '::database';

            if (!$this->getCache($cacheKey, $this->databaseArray)) {
                $this->databaseArray = $this->pdo->query("select name,value from `" . $this->databaseTablename . "` where is_active = 1")->fetchAll(PDO::FETCH_KEY_PAIR);

                $this->setCache($cacheKey, $this->databaseArray);
            }
        }

        return $this->pick($value, $key, $this->databaseArray);
    }

    protected function pickFile(string $key, string $value): bool
    {
        if (!$this->fileLoaded) {
            $cacheKey = __METHOD__ . '::file';

            if (!$this->getCache($cacheKey, $this->fileArray)) {
                $filePath = __ROOT__ . DIRECTORY_SEPARATOR . trim($this->filePath, DIRECTORY_SEPARATOR) . '.php';

                if (!file_exists($filePath)) {
                    throw new SnippetException('Snippet config file "' . $filePath . '" not found.');
                }

                $fileArray = require $filePath;

                if (!is_array($fileArray)) {
                    throw new SnippetException('Snippet File didn\'t return an Array.');
                }

                $this->fileArray = $fileArray;

                $this->setCache($cacheKey, $this->fileArray);
            }
        }

        return $this->pick($value, $key, $this->fileArray);
    }

    protected function pickConfig(string $key, string $value): bool
    {
        return $this->pick($value, $key, $this->configArray);
    }

    protected function pick(string $value, string $key, array $search): bool
    {
        $found = false;

        if (isset($search[$key])) {
            $this->plucked = $search[$key];
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
