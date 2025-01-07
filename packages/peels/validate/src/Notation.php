<?php

declare(strict_types=1);

namespace peels\validate;

class Notation
{
    protected string $delimiter = '.';
    protected string $isNull;

    public function __construct(string $delimiter = null)
    {
        $this->delimiter = $delimiter ?? $this->delimiter;
        $this->isNull = chr(0);
    }

    public function changeDelimiter(string $delimiter): self
    {
        $this->delimiter = $delimiter;

        return $this;
    }

    public function set(array|object &$data, string $key, mixed $value): void
    {
        // if the dot notation doesn't even contain the dot separator treat as a regular array or stdClass
        if (strpos($key, $this->delimiter) === false) {
            if (is_object($data)) {
                $data->$key = $value;
            } else {
                $data[$key] = $value;
            }
        } else {
            if (!empty($this->delimiter)) {
                $keys = explode($this->delimiter, $key);

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

    public function get(array|object $data = null, string $key, mixed $default = null): mixed
    {
        // this function has multiple returns
        if (strpos($key, $this->delimiter) === false) {
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
            $keys = explode($this->delimiter, $key);

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

    public function isset(mixed &$data, string $key): bool
    {
        return $this->get($data, $key, $this->isNull) !== $this->isNull;
    }

    public function unset(mixed &$data, string $key): void
    {
        // if the dot notation doesn't even contain the dot separator treat as a regular array or stdClass
        if (strpos($key, $this->delimiter) === false) {
            if (is_object($data)) {
                unset($data->$key);
            } else {
                unset($data[$key]);
            }
        } else {
            $keys = explode($this->delimiter, $key);

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
    public function flatten(array $lines, string $prepend = ''): array
    {
        $flatten = [];

        foreach ($lines as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $flatten[] = $this->flatten($value, $prepend . $key . $this->delimiter);
            } else {
                $flatten[] = [$prepend . $key => $value];
            }
        }

        return array_merge(...$flatten);
    }

    /**
     * Convert array to notation
     */
    public function expand(array $array): array
    {
        $newArray = [];

        foreach ($array as $key => $value) {
            $dots = explode($this->delimiter, $key);

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
