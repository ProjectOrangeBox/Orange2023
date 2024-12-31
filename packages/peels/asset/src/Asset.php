<?php

declare(strict_types=1);

namespace peels\asset;

use peels\asset\Priority;
use orange\framework\traits\ConfigurationTrait;
use peels\asset\Exceptions\AssetException;
use peels\asset\Interfaces\AssetInterface;
use peels\asset\Interfaces\PriorityInterface;
use orange\framework\interfaces\DataInterface;

class Asset implements AssetInterface
{
    use ConfigurationTrait;

    protected array $config = [];

    private static AssetInterface $instance;

    protected PriorityInterface $priority;
    protected DataInterface $data;

    public function __construct(array $config, ?DataInterface $data = null)
    {
        // orange provided function
        $this->config = $this->mergeWithDefault($config);

        $this->priority = new Priority();

        // this maybe null
        $this->data = $data;

        if (!defined('PAGE_MIN')) {
            define('PAGE_MIN', $this->config['page min']);
        }

        foreach ($this->config['inject'] as $function => $records) {
            foreach ($records as $args) {
                \call_user_func_array([$this, $function], $args);
            }
        }
    }

    public static function getInstance(array $configAsset, DataInterface $data)
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($configAsset, $data);
        }

        return self::$instance;
    }

    public function has(string $name): bool
    {
        return $this->priority->has($name);
    }

    /**
     * get a asset
     */
    public function get(string $name): string
    {
        $content = '';

        if ($this->priority->has($name)) {
            $content = $this->priority->get($name);
        }

        return in_array($name, $this->config['trim']) ? trim($content) : $content;
    }

    /* returns the HTML for a link */
    public function linkHTML(string $file): string
    {
        return $this->elementHTML('link', \array_replace($this->config['link attributes'], ['href' => $file]));
    }

    /* returns the HTML for a script */
    public function scriptHTML(string $file): string
    {
        return $this->elementHTML('script', \array_replace($this->config['script attributes'], ['src' => $file]));
    }

    /* convert array into HTML html element or meta for example */
    public function elementHTML(string $element, array $attributes, string $content = '', array $data = null): string
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $attributes['data-' . $this->stripFromStart($key, 'data-')] = $value;
            }
        }

        return (in_array($element, ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'])) ?
            '<' . $element . $this->stringifyAttributes($attributes) . '/>' :
            '<' . $element . $this->stringifyAttributes($attributes) . '>' . $content . '</' . $element . '>';
    }

    /**
     * Add Wrapper
     * script, link, domready, title, foo, bar
     *
     * $asset->foo('this value');
     */
    public function __call(string $name, array $arguments): self
    {
        // value
        if (!is_string($arguments[0])) {
            throw new AssetException('Asset Error Argument 1 must be a string.');
        }

        // priority
        if (!isset($arguments[1]) || !\is_int($arguments[1])) {
            $arguments[1] = Priority::NORMAL;
        }

        $this->add($name, $arguments[0], $arguments[1]);

        return $this;
    }

    public function scriptFile($file = '', int $priority = Priority::NORMAL): self
    {
        $html = (strpos($file, '//') === false) ? $this->scriptHTML($file) : $file;

        $this->add($this->config['data variable name']['script'], trim($html) . PHP_EOL, $priority);

        return $this;
    }

    public function scriptFiles(array $array, int $priority = Priority::NORMAL): self
    {
        foreach ($array as $arg1 => $arg2) {
            if (is_int($arg1)) {
                $arg1 = $arg2;
                $arg2 = $priority;
            }

            $this->scriptFile($arg1, $arg2);
        }

        return $this;
    }

    public function linkFile($file = '', int $priority = Priority::NORMAL): self
    {
        $html = (strpos($file, '//') === false) ? $this->linkHTML($file) : $file;

        $this->add($this->config['data variable name']['link'], trim($html) . PHP_EOL, $priority);

        return $this;
    }

    /* must be in filename & Priority format */
    public function linkFiles(array $array, int $priority = Priority::NORMAL): self
    {
        foreach ($array as $arg1 => $arg2) {
            if (is_int($arg1)) {
                $arg1 = $arg2;
                $arg2 = $priority;
            }

            $this->linkFile($arg1, $arg2);
        }

        return $this;
    }

    public function javascriptVariable(string $key, $value, int|bool $raw = false, int $priority = Priority::NORMAL): self
    {
        // if they pass the priority in arg 3 then swap
        if (is_int($raw)) {
            $priority = $raw;
            $raw = false;
        }

        if ($raw) {
            $value = 'var ' . $key . '=' . $value . ';';
        } else {
            if (is_scalar($value)) {
                $encode = '"' . str_replace('"', '\"', $value) . '"';
            } else {
                $encode =  json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
            }

            $value = 'var ' . $key . '=' . $encode . ';';
        }

        $this->add($this->config['data variable name']['js variables'], $value, $priority);

        return $this;
    }

    public function javascriptVariables(array $array): self
    {
        foreach ($array as $arg1 => $arg2) {
            $this->javascriptVariable($arg1, $arg2);
        }

        return $this;
    }

    public function bodyClass(string|array $class, int $priority = Priority::NORMAL): self
    {
        $classes = (is_string($class)) ? explode(' ', $class) : (array)$class;

        foreach ($classes as $class) {
            $this->add($this->config['data variable name']['body class'], ' ' . trim($class), $priority);
        }

        return $this;
    }

    /** protected */

    protected function add(string $name, string $content, int $priority = Priority::NORMAL): self
    {
        $this->priority->add($name, $content, $priority);

        // add it to the data object if it's not null
        if ($this->data) {
            $this->data[$name] = $this->get($name);
        }

        return $this;
    }

    protected function stripFromStart(string $string, string $strip): string
    {
        return (substr($string, 0, strlen($strip)) == $strip) ? substr($string, strlen($strip)) : $string;
    }

    protected function stringifyAttributes($attributes, $js = false)
    {
        if (empty($attributes)) {
            return null;
        }

        if (is_string($attributes)) {
            return ' ' . $attributes;
        }

        $attributes = (array) $attributes;

        $attributesString = '';

        foreach ($attributes as $key => $val) {
            $attributesString .= ($js) ? $key . '=' . $val . ',' : ' ' . $key . '="' . $val . '"';
        }

        return rtrim($attributesString, ',');
    }
}
