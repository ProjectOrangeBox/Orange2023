<?php

declare(strict_types=1);

use dmyers\orange\exceptions\ConfigFileNotFound;
use dmyers\orange\exceptions\FileNotFound;
use dmyers\orange\exceptions\InvalidConfigurationValue;
use dmyers\orange\interfaces\ContainerInterface;

// if you provide your own container override this
if (!function_exists('container')) {
    function container(): ContainerInterface
    {
        return dmyers\orange\Container::getInstance();
    }
}

/**
 * This is used to merge a config file which returns an array with a variable which contains an array
 */
if (!function_exists('mergeDefaultConfig')) {
    /**
     * Used to load default config
     *
     * $this->config = mergeDefaultConfig($config,__DIR__.'/config/myClassLocalDefaultfConfig.php');
     *
     */
    function mergeDefaultConfig(array &$current, string $absFilePath): array
    {
        if (!\file_exists($absFilePath)) {
            throw new ConfigFileNotFound($absFilePath);
        }

        $defaultConfig = include $absFilePath;

        if (!is_array($defaultConfig)) {
            throw new InvalidConfigurationValue('"' . $absFilePath . '" did not return an array.');
        }

        return array_replace_recursive($defaultConfig, $current);
    }
}

/**
 * Easy Access to logging
 * works only if logging service exists
 * 
 * override as needed
 */
if (!function_exists('logMsg')) {
    function logMsg(mixed $level, string $msg): void
    {
        if (function_exists('container')) {
            $container = container();

            // don't throw an error if it's not available
            if ($log = $container::getServiceIfExists('log')) {
                $levelAsInt = $log->convert2($level, true);
                $log->write($levelAsInt, $msg);
            }
        }
    }
}

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

        appEnv(array_replace_recursive(appEnv(), $env));
    }
}

/**
 * fetchEnv with required default
 * use this function instead of plain old $_ENV
 * this allows easier mocking
 * and provides a default if the env value doesn't exist
 * a default should always be set for security
 */
if (!function_exists('fetchEnv')) {
    function fetchEnv(string $key, $default = '__#NOVALUE#__') /* mixed */
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

        if ($env === null) {
            $env = $_ENV;
        }

        if ($replace !== null) {
            $env = $replace;
        }

        return $env;
    }
}

/**
 * Great for local cache files because the file is written atomically
 * that way another thread doesn't read a 1/2 written file
 */
if (!function_exists('file_put_contents_atomic')) {
    function file_put_contents_atomic(string $filePath, string $content, int $flags = 0, $context = null): int|false
    {
        // multiple exits

        $tempFilePath = $filePath . \hrtime(true);
        $strlen = strlen($content);

        if (file_put_contents($tempFilePath, $content, $flags, $context) !== $strlen) {
            return false;
        }

        // atomic function
        if (rename($tempFilePath, $filePath, $context) === false) {
            return false;
        }

        /* flush from the cache */
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($filePath, true);
        } elseif (function_exists('apc_delete_file')) {
            apc_delete_file($filePath);
        }

        return $strlen;
    }
}

/**
 * default exception handler
 *
 * override as needed
 */
if (!function_exists('orangeExceptionHandler')) {
    function orangeExceptionHandler(Throwable $exception): void
    {
        _lowleveldeath(json_encode([
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'line' => $exception->getLine(),
            'file' => $exception->getFile(),
            'class' => get_class($exception),
        ], JSON_PRETTY_PRINT), 500);
    }

    set_exception_handler('orangeExceptionHandler');
}

/**
 * default error handler
 *
 * override as needed
 */
if (!function_exists('orangeErrorHandler')) {
    function orangeErrorHandler($severity, $message, $filepath, $line)
    {
        if (!(error_reporting() & $severity)) {
            // This error code is not included in error_reporting, so let it fall
            // through to the standard PHP error handler
            return false;
        }

        _lowleveldeath(json_encode([
            'severity' => $severity,
            'message' => $message,
            'filepath' => $filepath,
            'line' => $line,
        ], JSON_PRETTY_PRINT), 500);

        return true;
    }

    set_error_handler('orangeErrorHandler');
}

/**
 * low level death
 * handles throwing a error before error service might be setup
 */
if (!function_exists('_lowleveldeath')) {
    function _lowleveldeath(string $text, int $errorCode = 500): void
    {
        if (function_exists('container')) {
            $container = container();

            if ($container->has('error')) {
                $container->error->reset()->showError($text, $errorCode);
            }
        } else {
            // error service not setup
            if (isset($_SERVER['SERVER_PROTOCOL'])) {
                header($_SERVER['SERVER_PROTOCOL'] . ' ' . $errorCode . ' Internal Server Error', true, $errorCode);
            }

            $text = (defined('ENVIRONMENT') && ENVIRONMENT == 'production') ? $text : $errorCode;

            echo '<pre>Error: ' . PHP_EOL . $text . PHP_EOL . '</pre>';
        }

        exit(1);
    }
}

/**
 * add the "missing" concat function
 */
if (!function_exists('concat')) {
    function concat(): string
    {
        return implode('', func_get_args());
    }
}

/**
 * provide reading values using dot notation
 * or really any notation using the last argument into an arrays
 */
if (!function_exists('getDotNotation')) {
    function getDotNotation($input, string $dotNotation, $default = null, string $dot = '.')
    {
        if (!empty($dotNotation) && !empty($dot)) {
            $keys = explode($dot, $dotNotation);

            foreach ($keys as $key) {
                if (is_array($input)) {
                    if (isset($input[$key])) {
                        $input = $input[$key];
                    } else {
                        return $default;
                    }
                } elseif (is_object($input)) {
                    if (isset($input->$key)) {
                        $input = $input->$key;
                    } else {
                        return $default;
                    }
                } else {
                    return $default;
                }
            }
        }

        return $input;
    }
}

/**
 * wrapper to read a config value
 */
if (!function_exists('config')) {
    function config(string $filename, string $key, mixed $default = null)
    {
        // throws error if service missing
        return container()::getService('config')->get($filename, $key, $default);
    }
}
