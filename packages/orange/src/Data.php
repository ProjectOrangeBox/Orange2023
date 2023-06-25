<?php

declare(strict_types=1);

namespace dmyers\orange;

use ArrayObject;
use dmyers\orange\interfaces\DataInterface;

class Data extends ArrayObject implements DataInterface
{
    private static DataInterface $instance;

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function merge(array $replaceArray): self
    {
        $this->exchangeArray(array_replace_recursive((array)$this, $replaceArray));

        return $this;
    }
}
