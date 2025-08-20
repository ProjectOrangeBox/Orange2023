<?php

declare(strict_types=1);

namespace peels\console;

class BitWise
{
    protected int $level = 0;
    protected static $flags = [];

    public function __construct(?array $flagValues = null)
    {
        if ($flagValues) {
            $this->setFlagValues($flagValues);
        }
    }

    /**
     * grab values statically
     *
     * BitWise::ERROR();
     *
     * @param string $name
     * @param array $arguments (unused)
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments): mixed
    {
        return static::$flags[strtoupper($name)] ?? 0;
    }

    /**
     *
     * @param string $name
     * @return int
     */
    public function getFlag(string $name): int
    {
        return static::$flags[strtoupper($name)] ?? 0;
    }

    /**
     *
     * @param string $name
     * @param int $int
     * @return void
     */
    public function setFlagValue(string $name, int $int): void
    {
        static::$flags[strtoupper($name)] = $int;
    }

    /**
     *
     * @param array $nameValue
     * @return void
     */
    public function setFlagValues(array $nameValue): void
    {
        foreach ($nameValue as $name => $value) {
            $this->setFlagValue($name, $value);
        }
    }

    /**
     *
     * @param array $names
     * @return void
     */
    public function autoSetFlagValues(array $names): void
    {
        $value = 1;

        foreach ($names as $name) {
            $this->setFlagValue($name, $value);
            $value = $value + $value;
        }
    }

    /**
     * Set the current bitwise (binary) value
     *
     * @param int $level
     * @return void
     */
    public function set(int $level): void
    {
        $this->level = $level;
    }

    /**
     * Get the current Bitwise (binary) value
     *
     * @return int
     */
    public function get(): int
    {
        return $this->level;
    }

    /**
     * Is a specific bitwise "bit" on (true) or off (false)
     *
     * @param int $flag
     * @return bool
     */
    public function isFlagSet(int $flag): bool
    {
        return ($this->level & $flag) == $flag;
    }

    /**
     * Set a specific bitwise "bit" on (true) or off (false)
     *
     * @param int $flag
     * @param bool $value
     * @return int
     */
    public function setFlag(int $flag, bool $value): int
    {
        if ($value) {
            $this->level |= $flag;
        } else {
            $this->level &= ~$flag;
        }

        return $this->level;
    }
}
