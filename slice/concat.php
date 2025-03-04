#!/usr/bin/env php
<?php

// track changes
$hashes = [];

require __DIR__ . '/inc/minJs.php';
require __DIR__ . '/inc/minCss.php';

while (1 == 1) {
    $object = json_decode(file_get_contents(__DIR__ . '/concat.json'));

    if ($object->js) {
        if (is_array($object->js->compress)) {
            foreach ($object->js->compress as $file) {
                if ($hashes[$file] ?? '' != md5_file(__DIR__ . $file)) {
                    compileJs($object->js->compressed, $object->js->compress, $hashes);
                    break;
                }
            }
        }
    }

    if ($object->css) {
        if (is_array($object->css->compress)) {
            foreach ($object->css->compress as $file) {
                if ($hashes[$file] ?? '' != md5_file(__DIR__ . $file)) {
                    compileCss($object->css->compressed, $object->css->compress, $hashes);
                    break;
                }
            }
        }
    }

    sleep(1);
}

function compileJs(string $compressedFilePath, array $files, &$hashes): void
{
    $compiled = '';

    foreach ($files as $file) {
        if (!realpath(__DIR__ . $file)) {
            die('can not find "' . __DIR__ . $file . '".');
        }

        $hashes[$file] = md5_file(__DIR__ . $file);

        $js = file_get_contents(__DIR__ . $file);

        $minJs = (strpos($file, '.min.') === false) ? \JShrink\Minifier::minify($js) : $js;

        $compiled .= $minJs . PHP_EOL;
    }

    file_put_contents(__DIR__ . $compressedFilePath, $compiled);
}

function compileCss(string $compressedFilePath, array $files, &$hashes): void
{
    $compiled = '';

    foreach ($files as $file) {
        if (!realpath(__DIR__ . $file)) {
            die('can not find "' . __DIR__ . $file . '".');
        }

        $hashes[$file] = md5_file(__DIR__ . $file);

        $css = file_get_contents(__DIR__ . $file);

        $minCss = (strpos($file, '.min.') === false) ? CssMinifer::minify($css) : $css;

        $compiled .= $minCss . PHP_EOL;
    }

    file_put_contents(__DIR__ . $compressedFilePath, $compiled);
}
