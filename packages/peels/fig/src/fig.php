<?php

declare(strict_types=1);

use orange\framework\interfaces\DataInterface;

// namespace global

/**
 *
 * These are a few different view "template" functions
 *
 */
class fig
{
    // allow the fig methods access to this
    // this should have all of the view accessable values
    public static DataInterface $data;

    public const PRIORITY_LOWEST = 10;
    public const PRIORITY_LOW = 20;
    public const PRIORITY_NORMAL = 50;
    public const PRIORITY_HIGH = 80;
    public const PRIORITY_HIGHEST = 90;

    public const BEFORE = -1;
    public const NORMAL = 0;
    public const AFTER = 1;
    public const PREPEND = -1;
    public const APPEND = 1;

    protected static $pluginPaths = [];
    protected static $loadedPlugins = [];

    public static function configure(array $configFig, DataInterface $data)
    {
        logMsg('INFO', __METHOD__);

        // publicly accessable view data object
        self::$data = $data;

        // add our local fig path
        fig::addPath(__DIR__ . '/figs');

        // an array of additional fig plugin locations
        if (isset($configFig['plugins directories']) && is_array($configFig['plugins directories'])) {
            fig::addPaths($configFig['plugins directories']);
        }
    }

    public static function addPath(string $path, bool $first = false): void
    {
        logMsg('INFO', __METHOD__);

        $path = rtrim($path, DIRECTORY_SEPARATOR);

        if ($first) {
            // add to beginning of search array
            array_unshift(self::$pluginPaths, $path);
        } else {
            // append to the end of search array
            array_push(self::$pluginPaths, $path);
        }
    }

    public static function addPaths(array $paths, bool $first = false): void
    {
        foreach ($paths as $path) {
            self::addPath($path, $first);
        }
    }

    public static function setPlugins(array $absPaths): void
    {
        self::$loadedPlugins = $absPaths;
    }

    public static function __callStatic($name, $arguments)
    {
        logMsg('INFO', __METHOD__ . ' ' . $name);

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
        $name = strtolower($name);

        if (!isset(self::$loadedPlugins[$name])) {
            foreach (self::$pluginPaths as $path) {
                $fullpath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name . '.php';

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
