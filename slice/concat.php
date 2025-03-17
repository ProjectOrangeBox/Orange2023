#!/usr/bin/env php
<?php

$concat = new concat();

$concat->watch();


class concat
{
    // track changes
    protected array $hashes = [];
    protected int $sleep = 1;
    protected mixed $object;
    protected string $configFile;

    public function __construct()
    {
        require __DIR__ . '/inc/minJs.php';
        require __DIR__ . '/inc/minCss.php';

        $this->configFile = __DIR__ . '/concat.json';
    }

    public function watch()
    {
        while (1 == 1) {
            $this->object = json_decode(file_get_contents($this->configFile));

            if ($this->object !== null) {
                $this->group('js');
                $this->group('css');
            } else {
                echo 'x';
            }

            sleep($this->sleep);
        }
    }

    protected function group(string $name): void
    {
        if ($this->object->$name) {
            if (is_array($this->object->$name->compress)) {
                foreach ($this->object->$name->compress as $file) {
                    $filePath = __DIR__ . $file;

                    if (file_exists($filePath)) {
                        $this->set($filePath);

                        if ($this->hashes[$filePath] != md5_file($filePath)) {
                            $this->concat($name);
                            break;
                        }
                    } else {
                        echo 'Can not find "' . $filePath . '".' . PHP_EOL;
                    }
                }
            }
        }
    }

    protected function concat(string $compressor): void
    {
        $complete = '';

        $files = $this->object->$compressor->compress;
        $compressedFilePath = $this->object->$compressor->compressed;

        foreach ($files as $file) {
            $filePath = __DIR__ . $file;

            if (file_exists($filePath)) {
                $contents = file_get_contents($filePath);

                $md5 = md5($contents);

                $this->set($filePath);

                if ($this->hashes[$filePath] != $md5) {

                    $this->hashes[$filePath] = $md5;

                    if (strpos($file, '.min.') === false) {
                        echo date('H:i:s ') . $file . PHP_EOL;

                        switch ($compressor) {
                            case 'css':
                                $contents = CssMinifer::minify($contents);
                                break;
                            case 'js':
                                $contents = \JShrink\Minifier::minify($contents);
                                break;
                            default:
                                die('unknown compressor type ' . $compressor . PHP_EOL);
                                break;
                        }
                    }
                }

                $complete .= $contents . PHP_EOL;
            } else {
                echo 'can not find "' . $filePath . '".' . PHP_EOL;
            }
        }

        file_put_contents(__DIR__ . $compressedFilePath, $complete);
    }

    protected function set(string $filePath): void
    {
        if (!isset($this->hashes[$filePath])) {
            $this->hashes[$filePath] = 0;
        }
    }
}
