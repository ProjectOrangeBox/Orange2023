<?php

declare(strict_types=1);

namespace peels\language;

use peels\language\LanguageInterface;
use orange\framework\exceptions\InvalidValue;
use orange\framework\exceptions\filesystem\FileNotFound;
use orange\framework\exceptions\filesystem\DirectoryNotFound;
use orange\framework\exceptions\config\InvalidConfigurationValue;

/**
 * This could be extended to include
 * a database look up that overrides
 * the file based look up
 */
class Language implements LanguageInterface
{
    protected static ?Language $instance = null;

    protected array $config;
    protected array $loaded;
    protected string $separator;
    protected string $rootDirectory;
    protected string $lang;
    protected string $absoluteDirectory;

    protected function __construct(array $config)
    {
        $this->config = $config;

        if (!isset($this->config['directory'])) {
            throw new InvalidConfigurationValue('directory');
        }

        if (!realpath($this->config['directory'])) {
            throw new DirectoryNotFound($this->config['directory']);
        }

        $this->rootDirectory = realpath($this->config['directory']);

        if (!isset($this->config['default language'])) {
            throw new InvalidConfigurationValue('default language');
        }

        $this->checkLangDirectory($this->config['default language']);

        $this->separator = $config['separator'] ?? '.';
    }

    public static function getInstance(array $config): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public function use(string $lang): void
    {
        $this->checkLangDirectory($lang);
    }

    public function has(string $lang): bool
    {
        return realpath($this->rootDirectory . DIRECTORY_SEPARATOR . $lang) !== false;
    }

    public function line(string $tag, string $default = ''): string
    {
        if (strpos($tag, $this->separator) === false) {
            throw new InvalidValue('missing separator "' . $this->separator . '"');
        }

        list($filename, $key) = explode($this->separator, $tag, 2);

        if (!isset($this->loaded[$this->lang][$filename])) {
            $this->loaded[$this->lang][$filename] = $this->load($filename);
        }

        return $this->loaded[$this->lang][$filename][$key] ?? $default;
    }

    protected function load(string $filename): array
    {
        $fullPath = $this->absoluteDirectory . DIRECTORY_SEPARATOR . strtolower($filename) . '.php';

        if (!realpath($fullPath)) {
            throw new FileNotFound('could not locate language file ' . $fullPath);
        }

        $array = require $fullPath;

        if (!is_array($array)) {
            throw new InvalidValue();
        }

        return $array;
    }

    protected function checkLangDirectory(string $lang): void
    {
        if (!$this->has($lang)) {
            throw new DirectoryNotFound($lang);
        }

        $this->lang = $lang;

        $this->absoluteDirectory = $this->rootDirectory . DIRECTORY_SEPARATOR . $this->lang;
    }
}
