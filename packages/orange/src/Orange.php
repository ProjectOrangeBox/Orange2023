<?php

declare(strict_types=1);

use dmyers\orange\Container;
use dmyers\orange\exceptions\InvalidValue;
use dmyers\orange\exceptions\ConfigFileNotFound;
use dmyers\orange\interfaces\ContainerInterface;
use dmyers\orange\exceptions\InvalidConfigurationValue;

define('NOVALUE', '__#NOVALUE#__');

if (!function_exists('http')) {
    /**
     * required:
     *
     * 'config folder' the absolute path to where the configuration files are stored
     * 'environment' the current environment (different for each system and usually set using fetchEnv('ENVIRONMENT')
     * 'services' the absolute path to the services config file. usually /app/config/services.php
     *
     * optional:
     *
     * 'bootstrap file' the absolute path to a very early loaded bootstrap file
     *
     */
    function http(array $config): ContainerInterface
    {
        // call bootstrap function
        $container = bootstrap($config);

        // call event
        $container->events->trigger('before.router', $container->input);

        // match uri & method to route
        $container->router->match($container->input->requestUri(), $container->input->requestMethod());

        // call event
        $container->events->trigger('before.controller', $container->router, $container->input);

        // dispatch route
        $container->dispatcher->call($container->router);

        // call event
        $container->events->trigger('before.output', $container->router, $container->input, $container->output);

        // send header, status code and output
        $container->output->send();

        // call event
        $container->events->trigger('before.shutdown', $container->router, $container->input, $container->output);

        // return container
        return $container;
    }
}

if (!function_exists('cli')) {
    function cli(array $config): ContainerInterface
    {
        // no events, routes, "default" output
        return bootstrap($config);
    }
}

if (!function_exists('bootstrap')) {
    function bootstrap(array $config): ContainerInterface
    {
        if (isset($config['timezone'])) {
            date_default_timezone_set($config['timezone']);
        } else {
            date_default_timezone_set('UTC');
        }

        define('DEBUG', $config['debug'] ?? false);
        define('ENVIRONMENT', $config['environment'] ?? 'production');

        if (file_exists($config['config folder'] . '/constants.php')) {
            require_once($config['config folder'] . '/constants.php');
        }

        if (file_exists($config['config folder'] . '/' . ENVIRONMENT . '/constants.php')) {
            require_once($config['config folder'] . '/' . ENVIRONMENT . '/constants.php');
        }

        switch (ENVIRONMENT) {
            case 'phpunit':
                ini_set('display_errors', '1');
                ini_set('display_startup_errors', '1');
                error_reporting(E_ALL ^ E_NOTICE);
                break;
            case 'development':
                ini_set('display_errors', '1');
                ini_set('display_startup_errors', '1');
                error_reporting(E_ALL ^ E_NOTICE);
                break;
            case 'testing':
                ini_set('display_errors', '1');
                ini_set('display_startup_errors', '1');
                error_reporting(E_ALL ^ E_NOTICE);
                break;
            default: //production
                ini_set('display_errors', '0');
                ini_set('display_startup_errors', '0');
        }

        if (extension_loaded('mbstring')) {
            define('MB_ENABLED', true);
            mb_substitute_character('none');
        } else {
            define('MB_ENABLED', false);
        }

        // Load custom bootstrap file if it's set
        if (isset($config['bootstrap file'])) {
            if (file_exists($config['bootstrap file'])) {
                require_once $config['bootstrap file'];
            } else {
                throw new ConfigFileNotFound('Could not locate your bootstrap file "' . $config['bootstrap file'] . '".');
            }
        }

        // make sure we have services
        if (!isset($config['services']) || !file_exists($config['services'])) {
            throw new ConfigFileNotFound('Could not locate the services configuration file.');
        }

        // load services from config
        $services = require $config['services'];

        // make sure they are a array
        if (!is_array($services)) {
            throw new InvalidValue('Services config file "' . $config['services'] . '" did not return an array.');
        }

        // setup the container
        $container = Container::setServices($services);

        // save bootstrapping config
        $container->set('$config', $config);

        // return the container we just made
        return $container;
    }
}

// override as needed
if (!function_exists('logMsg')) {
    function logMsg(mixed $level, mixed $msg = null): void
    {
        if (Container::getServiceIfExists('log')) {
            $logger = Container::getService('log');
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

    set_exception_handler('orangeExceptionHandler');
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

    set_error_handler('orangeErrorHandler');
}

if (!function_exists('_lowleveldeath')) {
    function _lowleveldeath(string $text, int $errorCode = 500): void
    {
        $container = Container::getServiceIfExists('error');

        if ($container) {
            $container->error->reset()->showError($text, $errorCode);
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
