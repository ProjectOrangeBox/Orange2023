<?php

declare(strict_types=1);

// namespace global

/**
 * 
 * These are a few different view "template" functions
 * 
 */
class fig
{
    const BEFORE = -1;
    const NONE = 0;
    const AFTER = 1;

    protected static $pluginPaths = [];
    protected static $loadedPlugins = [];

    public static function addPath(string $path): void
    {
        self::$pluginPaths[$path] = $path;
    }

    public static function addPaths(array $paths): void
    {
        foreach ($paths as $path) {
            self::addPath($path);
        }
    }

    public static function setPlugins(array $absPaths): void
    {
        self::$loadedPlugins = $absPaths;
    }

    public static function __callStatic($name, $arguments)
    {
        $functionName = 'fig_' . $name;

        // throws exception if not found
        $fullpath = self::findPlugIn($functionName);

        include_once $fullpath;

        return call_user_func_array($functionName, $arguments);
    }

    /**
     * find plugin and return abs path
     * 
     * throws exception if plugin not found
     */
    protected static function findPlugIn(string $name): string
    {
        if (!isset(self::$loadedPlugins[$name])) {
            foreach (self::$pluginPaths as $path) {
                $fullpath = rtrim($path, '/') . '/' . $name . '.php';

                if (file_exists($fullpath)) {
                    self::$loadedPlugins[$name] = $fullpath;

                    break;
                }
            }

            // was it loaded?
            if (!isset(self::$loadedPlugins[$name])) {
                throw new Exception('Could not locate fig plugin "' . $name . '".');
            }
        }

        return self::$loadedPlugins[$name];
    }
}
