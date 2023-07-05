<?php

use dmyers\orange\Container;
use dmyers\orange\exceptions\ConfigFileNotFound;
use dmyers\orange\exceptions\InvalidConfigurationValue;
use dmyers\orange\exceptions\ServiceNotFound;

/**
 * Used to load default config
 * When it comes to testing make sure to simply pass all config values in 
 * when creating the object.
 * This will then 100% override the defaults
 * 
 * $this->config = mergeDefaultConfig($config,__DIR__.'/config/myClassLocalDefaultfConfig.php');
 * 
 */

if (!function_exists('mergeDefaultConfig')) {
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
 * Use with caution
 * When it comes to testing make sure to override this in your bootstrap
 * because if everyone is using this willy nilly it's hard to determine what configs to
 * put in a know state for testing
 * it is better to pass all of the configurations a class needs when setting up the service.
 * It's also easier to pick out values from other config files in the service setup
 * because you would see exactly what is grabbed from other config files.
 * 
 * $config = $container->config->routes;
 *
 * $config['isHttps']  = $container->input->isHttpsRequest();
 * 
 * return Router::getInstance($config);
 * 
 * We can see immediately the router is getting 'isHttps' from the input service.
 * 
 */
if (!function_exists('config')) {
    function config(string $filename, string $name = null, $default = null)
    {
        $configValue = $default;

        if ($config = Container::getServiceIfExists('config')) {
            throw new ServiceNotFound('config');
        }

        $configArray = $config->get($filename);

        if ($name === null) {
            $configValue = $configArray;
        } else {
            $configValue = isset($configArray[$name]) ? $configArray[$name] : $default;
        }

        return $configValue;
    }
}
