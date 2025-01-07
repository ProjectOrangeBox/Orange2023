<?php

declare(strict_types=1);

namespace peels\model;

class StringBuilder
{
    protected array $append;
    protected string $separator;

    public function __construct(string $separator = ' ')
    {
        $this->separator($separator);
    }

    public function clear(): self
    {
        $this->append = [];

        return $this;
    }

    public function separator(string $separator): self
    {
        $this->separator = $separator;

        return $this;
    }

    public function append(): self
    {
        foreach (func_get_args() as $append) {
            $append = (string)$append;

            if (!empty($append)) {
                $this->append[] = $append;
            }
        }

        return $this;
    }

    public function get(string $prefix = '', string $suffix = '', ?string $separator = null): string
    {
        $separator = $separator === null ? $this->separator : $separator;

        return $prefix . implode($separator, $this->append) . $suffix;
    }

    public function getIfHas(string $prefix = '', string $suffix = '', string $separator = null): string
    {
        return $this->has() ? $this->get($prefix, $suffix, $separator) : '';
    }

    public function has(): bool
    {
        return !empty($this->append);
    }
}
