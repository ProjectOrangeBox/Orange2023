<?php

declare(strict_types=1);

namespace peels\benchmark;

use InvalidArgumentException;

class Benchmark
{
    protected static array $timeMarkers = [];
    protected static array $memoryMarkers = [];

    public static function mark(string $name): void
    {
        self::$timeMarkers[$name] = microtime();
        self::$memoryMarkers[$name] = memory_get_usage(false);
    }

    public static function elapsedTime(string $mark1, string $mark2, int $decimals = 4): string
    {
        self::checkMarkers(self::$timeMarkers, $mark1, $mark2);

        list($startmark, $startSeconds) = explode(' ', self::$timeMarkers[$mark1]);
        list($endmark, $endSeconds) = explode(' ', self::$timeMarkers[$mark2]);

        return number_format(($endmark + $endSeconds) - ($startmark + $startSeconds), $decimals);
    }

    public static function memoryUsage(string $mark1, string $mark2): string
    {
        self::checkMarkers(self::$memoryMarkers, $mark1, $mark2);

        return self::humanSize(self::$memoryMarkers[$mark2] - self::$memoryMarkers[$mark1]);
    }

    protected static function checkMarkers(array $array, string $mark1, string $mark2): void
    {
        if (!isset($array[$mark1])) {
            throw new InvalidArgumentException($mark1);
        }

        if (!isset($array[$mark2])) {
            throw new InvalidArgumentException($mark2);
        }
    }

    protected static function humanSize($size)
    {
        if ($size >= 1073741824) {
            $fileSize = round($size / 1024 / 1024 / 1024, 1) . 'GB';
        } elseif ($size >= 1048576) {
            $fileSize = round($size / 1024 / 1024, 1) . 'MB';
        } elseif ($size >= 1024) {
            $fileSize = round($size / 1024, 1) . 'KB';
        } else {
            $fileSize = $size . ' bytes';
        }
        return $fileSize;
    }
}
