<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\exceptions\InvalidValue;
use orange\framework\exceptions\MissingRequired;

trait ConfigurationTraits
{
    /**
     * This allows us to call $object->change('fooBar', 123) on the class which will
     * set the value $this->fooBar = 123; with type checking
     *
     * $this->changeableTypeCheck['fooBar'=>'is_integer'];
     *
     * @param string $name
     * @param mixed $value
     * @return ConfigurationTraits
     * @throws MissingRequired
     * @throws InvalidValue
     */
    public function change(string $name, mixed $value): self
    {
        if (!property_exists($this, 'changeableTypeCheck')) {
            throw new MissingRequired('Change not supported');
        }

        if (!is_array($this->changeableTypeCheck)) {
            throw new InvalidValue('changeableTypeCheck is not an array.');
        }

        // convert a human readable name to a variable name
        // convert 'Shipping Carrier' to 'shippingCarrier'
        $name = $this->camelize($name, false);

        if (!isset($this->changeableTypeCheck[$name])) {
            throw new InvalidValue('Cannot set ' . $name);
        }

        $typeCheck = $this->changeableTypeCheck[$name];

        if (function_exists($typeCheck)) {
            if (!$typeCheck($value)) {
                throw new InvalidValue($value . ' is not ' . $typeCheck);
            }
        } elseif (!$value instanceof $typeCheck) {
            throw new InvalidValue($value . ' is not ' . $typeCheck);
        }

        $method = 'set' . $this->camelize($name, true);

        // only call if the method exists
        if (method_exists($this, $method)) {
            $this->$method($value);
        } elseif (property_exists($this, $name)) {
            // set value
            $this->$name = $value;
        } else {
            throw new InvalidValue('property or set method not found ' . $name);
        }

        return $this;
    }

    protected function mergeWithDefault(array $config, string $absolutePath, bool $recursive = true): array
    {
        // if the absolute path to the file does not exsist try to auto detect __DIR__.'/config/{name}.php
        if (!file_exists($absolutePath)) {
            $absolutePath = dirname((new \ReflectionClass(get_class($this)))->getFileName()) . '/config/' . $absolutePath . '.php';
        }

        // the Application::mergeDefaultConfig(...) method will throw an exception if it's not found
        return Application::mergeDefaultConfig($config, $absolutePath, $recursive);
    }

    /**
     * This give use the ability to have config array keys of
     *
     * 'hmac minimum length'
     *
     * would call
     *
     * setHmacMinimumLength(...) to set the configuration value
     *
     * or
     *
     * BufferKeySize
     *
     * would call
     *
     * setBufferKeySize(...) to set the configuration value
     *
     */
    protected function setFromConfig(array $config, bool $throwException = false): void
    {
        foreach ($config as $name => $value) {
            // a config key of "default merge data"
            // would call setDefaultMergeData()
            $method = 'set' . $this->camelize($name, true);

            // only call if the method exists
            if (method_exists($this, $method)) {
                $this->$method($value);
            } else {
                if ($throwException) {
                    throw new InvalidValue('method not found ' . $method . '.');
                }
            }
        }
    }

    protected function assignFromConfig(array $config, bool $throwException = false): void
    {
        foreach ($config as $name => $value) {
            $name = $this->camelize($name, false);

            if (property_exists($this, $name)) {
                $this->$name = $value;
            } else {
                if ($throwException) {
                    throw new InvalidValue('property not found ' . $name . '.');
                }
            }
        }
    }

    /**
     * Normalize the name
     */
    protected function normalize(string $name): string
    {
        return mb_convert_case($name, MB_CASE_LOWER, mb_detect_encoding($name));
    }

    /**
     * Camelize
     *
     * Takes multiple words separated by spaces or underscores and camelizes them
     *
     * @param   string  $str    Input string
     * @return  string
     */
    protected function camelize(string $str, bool $ucFirst = false)
    {
        $converted = strtolower($str[0]) . substr(str_replace(' ', '', ucwords(preg_replace('/[\s_]+/', ' ', $str))), 1);

        return $ucFirst ? ucfirst($converted) : $converted;
    }

    /**
     * Underscore
     *
     * Takes multiple words separated by spaces and underscores them
     *
     * @param   string  $str    Input string
     * @return  string
     */
    protected function underscore(string $str)
    {
        return preg_replace('/[\s]+/', '_', trim(MB_ENABLED ? mb_strtolower($str) : strtolower($str)));
    }

    /**
     * Humanize
     *
     * Takes multiple words separated by the separator and changes them to spaces
     *
     * @param   string  $str        Input string
     * @param   string  $separator  Input separator
     * @return  string
     */
    protected function humanize(string $str, string $separator = '_')
    {
        return ucwords(preg_replace('/[' . preg_quote($separator) . ']+/', ' ', trim(MB_ENABLED ? mb_strtolower($str) : strtolower($str))));
    }

    /**
     * simple validation for variables
     *
     * config [
     *
     * ]
     */
    protected function validateConfig(array $config, array $rules): void
    {
        $errors = [];

        foreach ($config as $key => $value) {
            if (isset($rules[$key])) {
                // in this case we bail on the first on a giving key
                foreach (explode(',', $rules[$key]) as $rule) {
                    $type = gettype($config[$key]);
                    $hasOption = strpos($rule, '[');

                    if ($hasOption !== false) {
                        $option = substr($rule, $hasOption + 1, -1);
                        $rule = substr($rule, 0, $hasOption);
                    }

                    switch ($rule) {
                        case 'object':
                        case 'bool':
                        case 'integer':
                        case 'int':
                        case 'float':
                        case 'double':
                        case 'string':
                        case 'array':
                        case 'resource':
                            // convert int to integer
                            $rule = ($rule == 'int') ? 'integer' : $rule;

                            if ($type != $rule) {
                                $errors[] = $key . ' not an ' . $rule;
                            }
                            break;
                        case 'min':
                            switch ($type) {
                                case 'string':
                                    if (strlen($value) < $option) {
                                        $errors[] = $key . ' min is not ' . $option;
                                    }
                                    break;
                                case 'integer':
                                    if ($value < $option) {
                                        $errors[] = $key . ' min is not ' . $option;
                                    }
                                    break;
                                case 'array':
                                    if (count($value) < $option) {
                                        $errors[] = $key . ' min is not ' . $option;
                                    }
                                    break;
                                default:
                                    $errors[] = 'can not use min on ' . $type;
                            }
                            break;
                        case 'max':
                            switch ($type) {
                                case 'string':
                                    if (strlen($value) > $option) {
                                        $errors[] = $key . ' max is not ' . $option;
                                    }
                                    break;
                                case 'integer':
                                    if ($value > $option) {
                                        $errors[] = $key . ' max is not ' . $option;
                                    }
                                    break;
                                case 'array':
                                    if (count($value) > $option) {
                                        $errors[] = $key . ' max is not ' . $option;
                                    }
                                    break;
                                default:
                                    $errors[] = 'can not use max on ' . $type;
                            }
                            break;
                        case 'count':
                            if (count($value) != $option) {
                                $errors[] = $key . ' count is not ' . $option;
                            } else {
                                $errors[] = 'can not use count on ' . $type;
                            }
                            break;
                        case 'size':
                            switch ($type) {
                                case 'array':
                                    if (count($value) != $option) {
                                        $errors[] = $key . ' size does not match ' . $option;
                                    }
                                    break;
                                case 'string':
                                    if (strlen($value) != $option) {
                                        $errors[] = $key . ' size does not match ' . $option;
                                    }
                                    break;
                                default:
                                    $errors[] = 'can not use size on ' . $type;
                            }
                            break;
                        case 'class':
                            if (!$value instanceof $option) {
                                $errors[] = $key . ' is not an instance of ' . $option;
                            }
                            break;
                        default:
                            throw new InvalidValue('Unknown validate config rule ' . $rule);
                    }
                }
            }
        }

        if (!empty($errors)) {
            throw new InvalidValue('The following configuration key value pairs have errors ' . implode(', ', $errors) . '.');
        }
    }
}
