<?php

declare(strict_types=1);

namespace dmyers\orange;

use ArrayObject;
use dmyers\orange\interfaces\DataInterface;

class Data extends ArrayObject implements DataInterface
{
    private static DataInterface $instance;

    public function __construct(array $data = [])
    {
        $this->merge($data);
    }

    public static function getInstance(array $data = []): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($data);
        }

        return self::$instance;
    }

    public function merge(array $replaceArray): self
    {
        $this->exchangeArray(array_replace_recursive((array)$this, $replaceArray));

        return $this;
    }
}
