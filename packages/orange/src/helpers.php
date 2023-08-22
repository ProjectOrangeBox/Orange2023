<?php

use dmyers\orange\Container;
use dmyers\orange\exceptions\ConfigFileNotFound;
use dmyers\orange\exceptions\InvalidConfigurationValue;

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


// override as needed
if (!function_exists('logMsg')) {
    function logMsg(mixed $level, mixed $msg = null): void
    {
        if ($logger = Container::getServiceIfExists('log')) {
            $method = $logger->convert2($level);
            $logger->$method($msg);
        }
    }
}

/**
 * Merge our .env
 */
if (!function_exists('mergeEnv')) {
    function mergeEnv(string $absEnvFilePath): void
    {
        if (!file_exists($absEnvFilePath)) {
            die('.env file missing at "' . $absEnvFilePath . '".');
        }

        $env = parse_ini_file($absEnvFilePath, true, INI_SCANNER_TYPED);

        if (!is_array($env)) {
            die('ini file error "' . $absEnvFilePath . '" did not return an array.');
        }

        $_ENV = array_replace_recursive($_ENV, $env);
    }
}

/**
 * fetchEnv with required default
 * This is safer than just $_ENV[]
 */
if (!function_exists('fetchEnv')) {
    function fetchEnv(string $key, $default = '__#NOVALUE#__') /* mixed */
    {
        $searchArray = $_ENV;

        if (strpos($key, '.') !== false) {
            list($arg1, $arg2) = explode('.', $key, 2);

            if (!isset($_ENV[$arg1])) {
                throw new InvalidConfigurationValue('No env value found for "' . $arg1 . '".');
            }

            $searchArray = $_ENV[$arg1];
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
 * great for local cache files
 */
if (!function_exists('file_put_contents_atomic')) {
    function file_put_contents_atomic(string $filePath, string $content, int $flags = 0, $context = null): int|false
    {
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

    if (isset($_ENV['ENV']) && $_ENV['ENV'] != 'phpunit') {
        set_exception_handler('orangeExceptionHandler');
    }
}

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

    if (isset($_ENV['ENV']) && $_ENV['ENV'] != 'phpunit') {
        set_error_handler('orangeErrorHandler');
    }
}

if (!function_exists('_lowleveldeath')) {
    function _lowleveldeath(string $text, int $errorCode = 500): void
    {
        $container = Container::getInstance();

        if ($container) {
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

if (!function_exists('concat')) {
    function concat(): string
    {
        return implode('', func_get_args());
    }
}

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
