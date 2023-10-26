<?php

declare(strict_types=1);

use dmyers\orange\exceptions\ConfigFileNotFound;
use dmyers\orange\exceptions\InvalidConfigurationValue;

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
    function mergeDefaultConfig(array &$current, string $absFilePath, bool $recursive = true): array
    {
        if (!\file_exists($absFilePath)) {
            throw new ConfigFileNotFound($absFilePath);
        }

        $defaultConfig = include $absFilePath;

        if (!is_array($defaultConfig)) {
            throw new InvalidConfigurationValue('"' . $absFilePath . '" did not return an array.');
        }

        return ($recursive) ? array_replace_recursive($defaultConfig, $current) : array_replace($defaultConfig, $current);
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
 * add the "missing" concat function
 */
if (!function_exists('concat')) {
    function concat(): string
    {
        return implode('', func_get_args());
    }
}

if (!function_exists('element')) {
    function element(string $tag, array $attr = [], string $content = '', bool $escape = true)
    {
        $selfClosing = ['area', 'base', 'br', 'embed', 'hr', 'iframe', 'img', 'input', 'link', 'meta', 'param', 'source'];

        $html = '<' . $tag . ' ' . str_replace("=", '="', http_build_query($attr, '', '" ', PHP_QUERY_RFC3986)) . '">';

        if (!empty($content)) {
            $html .= ($escape) ? htmlentities($content) : $content;
        }

        if (!in_array($tag, $selfClosing)) {
            $html .= '</' . $tag . '>';
        }

        return $html;
    }
}

if (!function_exists('wrapArray')) {
    function wrapArray(array $array, string $prefix = '', string $suffix = '', string $separator = ''): string
    {
        $output = [];

        foreach ($array as $s) {
            $output[] = $prefix . $s . $suffix;
        }

        return implode($separator, $output);
    }
}
