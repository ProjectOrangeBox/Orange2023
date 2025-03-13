<?php

declare(strict_types=1);

namespace peels\collector;

use peels\collector\CollectorInterface;

class Collector implements CollectorInterface
{
    protected const ARRAY = 0;
    protected const HTML = 1;
    protected const JSON = 2;

    protected static array $instances = [];
    protected static string $defaultKey = '__#DEFAULT#__';

    protected array $collection = [];

    // constant from above
    protected int $as;
    protected array $options = [];
    protected bool $flat;
    protected bool $dedup;

    protected function __construct(array $config = [])
    {
        // default to array output
        $this->asArray();

        // default to not flatten the collection
        $this->flat = $config['flat'] ?? false;
        // default to not dedup the collection based on key
        $this->dedup = $config['dedup'] ?? false;
    }

    // you must pass a instance name that way you can have multiple collections this service manages
    public static function getInstance(?string $name = null, array $config = [])
    {
        // name your collection
        $name = $name ?? self::$defaultKey;

        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = new self($config);
        }

        return self::$instances[$name];
    }

    public function add(array|string $arg1, string|array|null $arg2 = null): self
    {
        if (is_string($arg1)) {
            if (is_array($arg2)) {
                foreach ($arg2 as $value) {
                    $this->addRecord($arg1, $value);
                }
            } elseif (is_null($arg2)) {
                $this->addRecord(self::$defaultKey, $arg1);
            } else {
                // must be a string
                $this->addRecord($arg1, $arg2);
            }
        } else {
            // must be a array
            $isAssociative = $this->isAssociative($arg1);

            foreach ($arg1 as $key => $value) {
                if ($isAssociative) {
                    $this->addRecord($key, $value);
                } else {
                    $this->addRecord(self::$defaultKey, $value);
                }
            }
        }

        return $this;
    }

    public function has(array|string|null $arg1 = null): bool
    {
        $has = true;

        foreach ($this->convert2Array($arg1) as $key) {
            if (!isset($this->collection[$key])) {
                $has = false;
                break;
            }
        }

        return $has;
    }

    public function hasOne(array|string $arg1): bool
    {
        $has = false;

        foreach ($this->convert2Array($arg1) as $key) {
            if (isset($this->collection[$key])) {
                $has = true;
                break;
            }
        }

        return $has;
    }

    public function collect(array|string|null $arg1 = null): array|string
    {
        foreach ($this->convert2Array($arg1) as $key) {
            $collectArray[$key] = $this->collection[$key] ?? [];
        }

        // make default none associative "0"
        if (isset($collectArray[self::$defaultKey])) {
            $collectArray[0] = $collectArray[self::$defaultKey];

            unset($collectArray[self::$defaultKey]);
        }

        return $this->format($this->as, $collectArray, $this->options);
    }

    public function collectAll(): array|string
    {
        return $this->collect('*');
    }

    public function remove(array|string|null $arg1 = null): self
    {
        foreach ($this->convert2Array($arg1) as $key) {
            unset($this->collection[$key]);
        }

        return $this;
    }

    public function removeAll(): self
    {
        $this->collection = [];

        return $this;
    }

    public function asArray(): self
    {
        $this->as = self::ARRAY;

        return $this;
    }

    public function asHtml(string $between = '', string $prefix = '', string $suffix = '', string $betweenPrefix = '', string $betweenSuffix = ''): self
    {
        $this->as = self::HTML;

        $this->options = [
            'between' => $between,
            'prefix' => $prefix,
            'suffix' => $suffix,
            'betweenPrefix' => $betweenPrefix,
            'betweenSuffix' => $betweenSuffix,
        ];

        return $this;
    }

    public function asJson(int $flags = 0): self
    {
        $this->as = self::JSON;

        $this->options = [
            'flags' => $flags
        ];

        return $this;
    }

    /* protected */

    protected function format(int $type, array $array, array $options, bool $reset = true): string|array
    {
        switch ($type) {
            case self::ARRAY:
                $formatted = $this->formatAsArray($array, $options);
                break;
            case self::HTML:
                // html
                $formatted = $this->formatAsHtml($array, $options);
                break;
            case self::JSON:
                // JSON
                $formatted = $this->formatAsJson($array, $options);
                break;
            default:
                throw new CollectorException('Unknown format ' . $type);
        }

        if ($reset) {
            $this->asArray();
        }

        return $formatted;
    }

    protected function formatAsHtml(array $array, array $options): string
    {
        $format = $options['prefix'];

        foreach (array_keys($array) as $key) {
            // optionally supports merging the key {{key}} or {{ key }}
            $format .= $this->swap($options['betweenPrefix'], (string)$key) . implode($this->swap($options['between'], (string)$key), $array[$key]) . $this->swap($options['betweenSuffix'], (string)$key);
        }

        $format .= $options['suffix'];

        return $format;
    }

    protected function formatAsJson(array $array, array $options): string
    {
        return json_encode($array, $options['flags']);
    }

    protected function formatAsArray(array $array, array $options): array
    {
        return $array;
    }

    protected function convert2Array(null|array|string $arg1): array
    {
        $arg1 = ($arg1 === '*') ? array_keys($this->collection) : $arg1;

        if ($arg1 === null) {
            $arg1 = [self::$defaultKey];
        } else {
            $arg1 = is_string($arg1) ? explode(',', $arg1) : $arg1;
        }

        return $arg1;
    }

    protected function isAssociative(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    protected function addRecord(string $key, string $value): void
    {
        if ($this->flat) {
            $this->collection[$key] = $value;
        } else {
            if ($this->dedup) {
                $this->collection[$key][sha1($value)] = $value;
            } else {
                $this->collection[$key][] = $value;
            }
        }
    }

    protected function swap(string $string, string $key): string
    {
        return str_replace(['{{key}}','{{ key }}'], $key, $string);
    }
}
