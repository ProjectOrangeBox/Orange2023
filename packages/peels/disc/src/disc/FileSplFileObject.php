<?php

declare(strict_types=1);

namespace peels\disc\disc;

use SplFileObject;

class FileSplFileObject extends SplFileObject
{
    /* wrappers for "f" methods */
    protected $handle;

    public function characters(int $length): string|false
    {
        return $this->fread($length);
    }

    public function write(string $string, ?int $length = null): int|false
    {
        return ($length) ? $this->fwrite($string, $length) : $this->fwrite($string);
    }

    public function writeLine(string $string, string $lineEnding = null): int|false
    {
        $lineEnding = ($lineEnding) ?? PHP_EOL;

        return $this->write($string . $lineEnding);
    }

    public function character(): string|false
    {
        return $this->characters(1);
    }

    public function line(): string
    {
        return $this->fgets();
    }

    public function lock(int $operation, int &$wouldBlock = null): bool
    {
        return $this->flock($operation, $wouldBlock);
    }

    public function position(int $position = null): int
    {
        return ($position) ? $this->fseek($this->handle, $position) : $this->ftell($this->handle);
    }

    public function flush(): bool
    {
        return $this->fflush();
    }
}
