<?php

declare(strict_types=1);

use dmyers\orange\exceptions\FileNotFound;
use dmyers\orange\exceptions\InvalidConfigurationValue;

/**
 * merge a file based .env file with the system based $_ENV
 *
 * This is stored in appEnv() for easy mocking
 */
if (!function_exists('mergeEnv')) {
    function mergeEnv(string $absEnvFilePath): void
    {
        if (!file_exists($absEnvFilePath)) {
            throw new FileNotFound('.env file missing at "' . $absEnvFilePath . '".');
        }

        $env = parse_ini_file($absEnvFilePath, true, INI_SCANNER_TYPED);

        if (!is_array($env)) {
            throw new FileNotFound('ini file error "' . $absEnvFilePath . '" did not return an array.');
        }

        // insert into appEnv
        appEnv(array_replace_recursive(appEnv(), $env));
    }
}

/**
 * fetchAppEnv with required default
 * use this function instead of plain old $_ENV
 * this allows easier mocking
 * and provides a default if the env value doesn't exist
 * a default should always be set for security
 */
if (!function_exists('fetchAppEnv')) {
    function fetchAppEnv(string $key, $default = '__#NOVALUE#__'): mixed
    {
        $searchArray = appEnv();

        if (strpos($key, '.') !== false) {
            list($arg1, $arg2) = explode('.', $key, 2);

            if (!isset($searchArray[$arg1])) {
                throw new InvalidConfigurationValue('No env value found for "' . $arg1 . '".');
            }

            $searchArray = $searchArray[$arg1];
            $key = $arg2;
        }

        $isset = isset($searchArray[$key]);

        if (!$isset && $default == '__#NOVALUE#__') {
            throw new InvalidConfigurationValue('No env value found for "' . $key . '" and no default value set.');
        }

        return ($isset) ? $searchArray[$key] : $default;
    }
}

/**
 * mockable unified env storage
 */
if (!function_exists('appEnv')) {
    function appEnv(array $replace = null): array
    {
        static $env = null;

        if ($replace !== null) {
            $env = $replace;
        } elseif ($env === null) {
            $env = $_ENV;
        }

        return $env;
    }
}
