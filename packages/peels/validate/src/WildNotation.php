<?php

declare(strict_types=1);

namespace peels\validate;

use InvalidArgumentException;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

class WildNotation
{
    protected array $array;
    protected string $delimiter = '.';
    protected string $wildcard  = '*';

    public function __construct(array $array = [])
    {
        $this->setArray($array);
    }

    public function setArray(array $array = []): self
    {
        $this->array = $array;

        return $this;
    }

    public function setDelimiter(string $delimiter): self
    {
        if ($delimiter === '') {
            throw new InvalidArgumentException('The delimiter must not be an empty string.');
        }

        $this->delimiter = $delimiter;

        return $this;
    }

    public function setWildcard(string $wildcard): self
    {
        if ($wildcard === '') {
            throw new InvalidArgumentException('The wildcard must not be an empty string.');
        }

        $this->wildcard = $wildcard;

        return $this;
    }

    public function get(string $path, mixed $default = null): mixed
    {
        if (isset($this->array[$path])) {
            $get =  $this->array[$path];
        } elseif ($path === $this->wildcard) {
            $get = $this->array;
        } else {
            $get = $this->search($path, $default);
        }

        return $get;
    }

    protected function search(string $path, mixed $default = null): mixed
    {
        $pathway = [];
        $flatArray = null;

        $segments = explode($this->delimiter, $path);
        $countSegments = count($segments);

        $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($this->array), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($it as $key => $value) {
            $pathway[$it->getDepth()] = $key;

            if ($it->getDepth() + 1 !== $countSegments) {
                continue;
            }

            if ($this->isRealPath($segments, $pathway)) {
                $flatArray[implode($this->delimiter, array_slice($pathway, 0, $it->getDepth() + 1))] = $value;
            }
        }

        if ($flatArray === null) {
            $val = $default;
        } else {
            $val = array_values($flatArray);

            if (is_countable($val) && count($val) === 1) {
                $val = $val[0];
            }
        }

        return $val;
    }

    protected function isRealPath(array $path, array $real): bool
    {
        $success = true;

        if ($path !== $real) {
            $index = 0;
            $success = false;

            foreach ($path as $item) {
                $val = $real[$index] ?? false;

                if (ctype_digit($item)) {
                    $item = (int) $item;
                }

                if ($val === $item) {
                    $success = true;
                } elseif ($item === $this->wildcard) {
                    $success = true;
                } else {
                    // bail on first non match
                    $success = false;
                    break;
                }

                $index++;
            }
        }

        return $success;
    }
}
