<?php

declare(strict_types=1);

namespace peels\asset;

use orange\framework\traits\ConfigurationTrait;
use peels\asset\Interfaces\PriorityInterface;

class Priority implements PriorityInterface
{
    use ConfigurationTrait;

    protected array $data = [];
    protected bool $sorted = false;

    public function has(string $name): bool
    {
        return isset($this->data[$this->normalize($name)]);
    }

    public function get(string $name): string
    {
        $name = $this->normalize($name);

        $outputText = '';

        if ($this->has($name)) {
            if (!$this->sorted) {
                /* sort priority */
                ksort($this->data[$name]);

                $this->sorted = true;
            }

            /* now build our output */
            foreach ($this->data[$name] as $value) {
                $outputText .= $value;
            }
        }

        return $outputText;
    }

    /* add something with priority */
    public function add(string $name, string $value, bool|int $append = true, int $priority = self::NORMAL): self
    {
        // if they pass the priority in arg 3 then swap
        if (is_int($append)) {
            $priority = $append;
            $append = true;
        }

        $name = $this->normalize($name);
        $order = floatval((string)$priority . (string)\hrtime(true));

        if (!$append) {
            $this->data[$name] = [];
        }

        $this->data[$name][$order] = $value;

        $this->sorted = false;

        return $this;
    }

    public function addMultiple(array $array, bool|int $append = true, int $priority = self::NORMAL): self
    {
        foreach ($array as $name => $value) {
            if (!is_string($name) || !is_string($value)) {
                throw new \InvalidArgumentException("Invalid name or value type.");            
            } else {
                $this->add($name, $value, $append, $priority);
            }
        }

        return $this;
    }
}
