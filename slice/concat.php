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
                if (!isset($hashes[__DIR__ . $file])) {
                    $hashes[__DIR__ . $file] = 0;
                }

                if ($hashes[__DIR__ . $file] != md5_file(__DIR__ . $file)) {
                    concat($object->js->compressed, $object->js->compress, $hashes, 'javascript');
                    break;
                }
            }
        }
    }

    if ($object->css) {
        if (is_array($object->css->compress)) {
            foreach ($object->css->compress as $file) {
                if (!isset($hashes[__DIR__ . $file])) {
                    $hashes[__DIR__ . $file] = 0;
                }

                if ($hashes[__DIR__ . $file] != md5_file(__DIR__ . $file)) {
                    concat($object->css->compressed, $object->css->compress, $hashes, 'css');
                    break;
                }
            }
        }
    }

    usleep(500);
}

function concat(string $compressedFilePath, array $files, &$hashes, string $compressor): void
{
    echo '.';

    $complete = '';

    foreach ($files as $file) {
        if (file_exists(__DIR__ . $file)) {
            $hashes[__DIR__ . $file] = md5_file(__DIR__ . $file);

            $contents = file_get_contents(__DIR__ . $file);

            if (strpos($file, '.min.') === false) {
                switch ($compressor) {
                    case 'css':
                        $contents = CssMinifer::minify($contents);
                        break;
                    case 'javascript':
                        $contents = \JShrink\Minifier::minify($contents);
                        break;
                }
            }

            $complete .= $contents . PHP_EOL;
        } else {
            echo 'can not find "' . __DIR__ . $file . '".' . PHP_EOL;
        }
    }

    file_put_contents(__DIR__ . $compressedFilePath, $complete);
}
