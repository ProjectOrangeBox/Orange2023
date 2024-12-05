<?php

declare(strict_types=1);

namespace orange\framework\helpers;

class Ary
{
    public static function remapKey(array $input, array $map): array
    {
        foreach ($input as $key => $value) {
            if (isset($map[$key])) {
                $input[$map[$key]] = $value;
                unset($input[$key]);
            }
        }

        return $input;
    }

    public static function remapValue(array $input, array $map): array
    {
        foreach ($input as $key => $value) {
            if (isset($map[$value])) {
                $input[$key] = $map[$value];
            }
        }

        return $input;
    }

    /*
     * wrap and array for output
     */
    public static function wrapArray(array $array, string $prefix = '', string $suffix = '', string $separator = '', string $parentPrefix = '', string $parentSuffix = ''): string
    {
        $output = [];

        foreach ($array as $string) {
            $output[] = $prefix . $string . $suffix;
        }

        return $parentPrefix . implode($separator, $output) . $parentSuffix;
    }

    /**
     * This will collapse a array with multiple values into a single key=>value pair
     */
    public static function makeAssociated(array $array, string $key = 'id', string $value = '*', string $sort = ''): array
    {
        $associativeArray = [];

        foreach ($array as $row) {
            if (is_object($row)) {
                if ($value == '*') {
                    $associativeArray[$row->$key] = $row;
                } else {
                    $associativeArray[$row->$key] = $row->$value;
                }
            } else {
                if ($value == '*') {
                    $associativeArray[$row[$key]] = $row;
                } else {
                    $associativeArray[$row[$key]] = $row[$value];
                }
            }
        }

        switch ($sort) {
            case 'desc':
            case 'd':
                krsort($associativeArray, SORT_NATURAL | SORT_FLAG_CASE);
                break;
            case 'asc':
            case 'a':
                ksort($associativeArray, SORT_NATURAL | SORT_FLAG_CASE);
                break;
            default:
        }

        return $associativeArray;
    }

    /**
     * Element
     *
     * Lets you determine whether an array index is set and whether it has a value.
     * If the element is empty it returns NULL (or whatever you specify as the default value.)
     */
    public static function element(string $item, array $array, mixed $default = null): mixed
    {
        return array_key_exists($item, $array) ? $array[$item] : $default;
    }

    // ------------------------------------------------------------------------

    /**
     * Random Element - Takes an array as input and returns a random element
     */
    public static function randomElement(array $array)
    {
        return $array[array_rand($array)];
    }

    // --------------------------------------------------------------------

    /**
     * Elements
     *
     * Returns only the array items specified. Will return a default value if
     * it is not set.
     */
    public static function elements($items, array $array, mixed $default = null): mixed
    {
        $return = [];

        is_array($items) || $items = [$items];

        foreach ($items as $item) {
            $return[$item] = array_key_exists($item, $array) ? $array[$item] : $default;
        }

        return $return;
    }
}
