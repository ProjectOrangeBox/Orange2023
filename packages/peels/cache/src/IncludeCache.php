<?php

declare(strict_types=1);

namespace peels\cache;

use orange\framework\exceptions\filesystem\DirectoryNotFound;
use orange\framework\exceptions\filesystem\DirectoryNotWritable;

class IncludeCache implements CacheInterface
{
    private static CacheInterface $instance;

    protected string $directory;
    protected string $parentDirectory = 'include';
    protected int $subDirectoryLength = 1;

    public function __construct(array $config)
    {
        if (!isset($config['directory'])) {
            throw new DirectoryNotFound();
        }

        $this->directory = $config['directory'];

        if (!realpath($this->directory)) {
            throw new DirectoryNotFound($this->directory);
        }

        if (!is_writable($this->directory)) {
            throw new DirectoryNotWritable($this->directory);
        }
    }

    public static function getInstance(array $config): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public function get(string $key): mixed
    {
        $completeDirectory = $this->buildFilePath($key);

        $content = null;

        if (file_exists($completeDirectory)) {
            $content = include $completeDirectory;
        }

        return $content;
    }

    public function set(string $key, mixed $value, int $ttl = null): bool
    {
        return $this->file_put_contents_atomic($this->buildFilePath($key), $this->convert2include($value)) > 0;
    }

    public function delete(string $key): bool
    {
        $completeDirectory = $this->buildFilePath($key);

        $bool = false;

        if (file_exists($completeDirectory)) {
            $bool = unlink($completeDirectory);
        }

        return $bool;
    }

    public function flush(): bool
    {
        foreach (glob($this->directory . '/' . $this->parentDirectory . '/*', GLOB_ONLYDIR) as $directory) {
            $this->recurseRmdir($directory);
        }

        return true;
    }

    public function getMulti(array $keys): array
    {
        $set = [];

        foreach ($keys as $key) {
            $set[$key] = $this->get($key);
        }

        return $set;
    }

    public function setMulti(array $data, int $ttl = null): array
    {
        $set = [];

        foreach ($data as $key => $value) {
            $set[$key] = $this->set($key, $value);
        }

        return $set;
    }

    public function deleteMulti(array $keys): array
    {
        $set = [];

        foreach ($keys as $key) {
            $set[$key] = $this->delete($key);
        }

        return $set;
    }

    public function increment(string $key, int $offset = 1, int $ttl = null): int
    {
        // unsupported
        return 0;
    }

    public function decrement(string $key, int $offset = 1, int $ttl = null): int
    {
        // unsupported
        return 0;
    }

    /* protected */

    protected function buildFilePath(string $key): string
    {
        $subPath = $this->directory . '/' . $this->parentDirectory . '/' . substr($key, 0, $this->subDirectoryLength);

        if (!is_dir($subPath)) {
            mkdir($subPath, 0777, true);
        }

        return $subPath . '/' . $key . '.php';
    }

    protected function convert2include(array $array): string
    {
        return '<?php' . PHP_EOL . PHP_EOL . 'declare(strict_types=1);' . PHP_EOL . PHP_EOL . 'return ' . var_export($array, true) . ';' . PHP_EOL;
    }

    protected function file_put_contents_atomic(string $filePath, string $content, int $flags = 0, $context = null): int|false
    {
        // !multiple exits

        $tempFilePath = $filePath . \hrtime(true);
        $strlen = strlen($content);

        if (file_put_contents($tempFilePath, $content, $flags, $context) !== $strlen) {
            return false;
        }

        // atomic function
        if (rename($tempFilePath, $filePath, $context) === false) {
            return false;
        }

        // flush from the cache
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($filePath, true);
        } elseif (function_exists('apc_delete_file')) {
            apc_delete_file($filePath);
        }

        return $strlen;
    }

    protected function recurseRmdir(string $directory)
    {
        $files = array_diff(scandir($directory), ['.', '..']);

        foreach ($files as $file) {
            (is_dir("$directory/$file") && !is_link("$directory/$file")) ? $this->recurseRmdir("$directory/$file") : unlink("$directory/$file");
        }

        return rmdir($directory);
    }
}
