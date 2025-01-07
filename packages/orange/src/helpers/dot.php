<?php

declare(strict_types=1);

namespace orange\framework\helpers;

/**
 * Work with arrays using dot notation
 *
 * These are all static functions
 */

class Dot
{
    protected static string $delimiter = '.';

    public static function changeDelimiter(string $delimiter): void
    {
        self::$delimiter = $delimiter;
    }

    public static function set(array|object &$data, string $key, mixed $value): void
    {
        // if the dot notation doesn't even contain the dot separator treat as a regular array or stdClass
        if (strpos($key, self::$delimiter) === false) {
            if (is_object($data)) {
                $data->$key = $value;
            } else {
                $data[$key] = $value;
            }
        } else {
            if (!empty(self::$delimiter)) {
                $keys = explode(self::$delimiter, $key);

                while (count($keys) > 1) {
                    $key = array_shift($keys);

                    // set if missing
                    if (is_object($data)) {
                        if (!isset($data->$key)) {
                            $data->$key = new \StdClass();
                        }

                        $data = &$data->$key;

                        $key = reset($keys);

                        $data->$key = $value;
                    } else {
                        if (!isset($data[$key])) {
                            $data[$key] = [];
                        }

                        $data = &$data[$key];

                        $key = reset($keys);

                        $data[$key] = $value;
                    }
                }
            }
        }
    }

    public static function get(array|object $data, string $key, mixed $default = null): mixed
    {
        // this function has multiple returns

        if (strpos($key, self::$delimiter) === false) {
            if (is_object($data)) {
                if (isset($data->$key)) {
                    $data = $data->$key;
                } else {
                    return $default;
                }
            } else {
                if (isset($data[$key])) {
                    $data = $data[$key];
                } else {
                    return $default;
                }
            }
        } else {
            $keys = explode(self::$delimiter, $key);

            foreach ($keys as $key) {
                if (is_array($data)) {
                    if (isset($data[$key])) {
                        $data = $data[$key];
                    } else {
                        return $default;
                    }
                } elseif (is_object($data)) {
                    if (isset($data->$key)) {
                        $data = $data->$key;
                    } else {
                        return $default;
                    }
                } else {
                    return $default;
                }
            }
        }

        return $data;
    }

    public static function isset(mixed &$data, string $key): bool
    {
        return self::get($data, $key, UNDEFINED) !== UNDEFINED;
    }

    public static function unset(mixed &$data, string $key): void
    {
        // if the dot notation doesn't even contain the dot separator treat as a regular array or stdClass
        if (strpos($key, self::$delimiter) === false) {
            if (is_object($data)) {
                unset($data->$key);
            } else {
                unset($data[$key]);
            }
        } else {
            $keys = explode(self::$delimiter, $key);

            while (count($keys) > 1) {
                $key = array_shift($keys);

                // set if missing
                if (is_object($data)) {
                    if (!isset($data->$key)) {
                        $data->$key = new \StdClass();
                    }

                    $data = &$data->$key;

                    $key = reset($keys);

                    unset($data->$key);
                } else {
                    if (!isset($data[$key])) {
                        $data[$key] = [];
                    }

                    $data = &$data[$key];

                    $key = reset($keys);

                    unset($data[$key]);
                }
            }
        }
    }

    /**
     * Convert Notation to Array
     */
    public static function flatten(array $lines, string $prepend = ''): array
    {
        $flatten = [];

        foreach ($lines as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $flatten[] = self::flatten($value, $prepend . $key . self::$delimiter);
            } else {
                $flatten[] = [$prepend . $key => $value];
            }
        }

        return array_merge(...$flatten);
    }

    /**
     * Convert array to notation
     */
    public static function expand(array $array): array
    {
        $newArray = [];

        foreach ($array as $key => $value) {
            $dots = explode(self::$delimiter, $key);

            if (count($dots) > 1) {
                $last = &$newArray[$dots[0]];
                foreach ($dots as $k => $dot) {
                    if ($k == 0) {
                        continue;
                    }

                    $last = &$last[$dot];
                }

                $last = $value;
            } else {
                $newArray[$key] = $value;
            }
        }

        return $newArray;
    }
}
