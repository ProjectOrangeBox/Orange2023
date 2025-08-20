<?php

declare(strict_types=1);

namespace peels\model;

class StringBuilder
{
    protected array $append;

    public function __construct(protected string $separator = ' ', protected bool $autoTrim = true)
    {
        $this->clear();
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
                $this->append[] = $this->autoTrim ? trim($append) : $append;
            }
        }

        return $this;
    }

    public function get(string $prefix = '', string $suffix = '', ?string $separator = null): string
    {
        return $prefix . implode($separator ?? $this->separator, $this->append) . $suffix;
    }

    public function getIfHas(string $prefix = '', string $suffix = '', ?string $separator = null): string
    {
        return $this->has() ? $this->get($prefix, $suffix, $separator) : '';
    }

    public function has(): bool
    {
        return !empty($this->append);
    }
}
