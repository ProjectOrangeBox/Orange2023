<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use orange\framework\exceptions\InvalidValue;
use orange\framework\exceptions\config\ConfigFileNotFound;

class unitTestHelper extends TestCase
{
    protected $instance;
    /* support for private / protected properties and methods */
    protected function mergeDefaultConfig(array $current, string $absFilePath, bool $recursive = true): array
    {
        if (!file_exists($absFilePath)) {
            throw new ConfigFileNotFound($absFilePath);
        }

        $defaultConfig = include $absFilePath;

        if (!is_array($defaultConfig)) {
            throw new InvalidValue('"' . $absFilePath . '" did not return an array.');
        }

        return ($recursive) ? array_replace_recursive($defaultConfig, $current) : array_replace($defaultConfig, $current);
    }

    protected function getPrivatePublic($attribute, $instance = null)
    {
        $instance = ($instance) ?? $this->instance;

        $getter = function () use ($attribute) {
            return $this->$attribute;
        };

        $closure = \Closure::bind($getter, $instance, get_class($instance));

        return $closure();
    }

    protected function setPrivatePublic($attribute, $value, $instance = null)
    {
        $instance = ($instance) ?? $this->instance;

        $setter = function ($value) use ($attribute) {
            $this->$attribute = $value;
        };

        $closure = \Closure::bind($setter, $instance, get_class($instance));

        $closure($value);
    }

    protected function callMethod(string $method, array $args = null, $instance = null)
    {
        $instance = ($instance) ?? $this->instance;

        $reflectionMethod = new ReflectionMethod($instance, $method);

        return (is_array($args)) ? $reflectionMethod->invokeArgs($instance, $args) : $reflectionMethod->invoke($instance);
    }

    protected function stripInvisible(string $string): string
    {
        return preg_replace('/[\x00-\x1F\x7F]/u', '', $string);
    }

    protected function ve($expression): void
    {
        if (!is_array($expression)) {
            $export = var_export($expression, true);
        } else {
            $export = var_export($expression, true);
            $export = preg_replace("/^([ ]*)(.*)/m", '$1$1$2', $export);
            $array = preg_split("/\r\n|\n|\r/", $export);
            $array = preg_replace(["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/"], [null, ']$1', ' => ['], $array);
            $export = join(PHP_EOL, array_filter(["["] + $array));
        }

        echo $export . PHP_EOL;

        echo '$this->assertEquals(' . $export . ',' . PHP_EOL;
    }
}
