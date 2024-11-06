<?php

declare(strict_types=1);

namespace orange\framework;

trait ConfigurationTraits
{
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
     * 'hmac minimum length'
     *
     * which automatically calls
     *
     * setHmacMinimumLength(...) to set the configuration value
     *
     * or
     *
     * BufferKeySize
     *
     * which automatically calls
     *
     * setBufferKeySize(...) to set the configuration value
     *
     */
    public function setFromConfig(array $config): void
    {
        foreach ($config as $name => $value) {
            $method = 'set' . str_replace(' ', '', ucwords($name));

            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }

    /**
     * Normalize the name
     */
    protected function normalizeName(string $name): string
    {
        return mb_convert_case($name, MB_CASE_LOWER, mb_detect_encoding($name));
    }
}
