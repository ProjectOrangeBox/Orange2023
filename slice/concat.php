#!/usr/bin/env php
<?php

$concat = new concat();

$concat->watch();


class concat
{
    // track changes
    protected array $hashes = [];
    protected array $contents = [];
    protected int $sleep = 1;
    protected mixed $object;
    protected string $configFile;
    protected string $null;
    protected string $complete = '';

    public function __construct()
    {
        require __DIR__ . '/inc/minJs.php';
        require __DIR__ . '/inc/minCss.php';

        $this->null = chr(0);

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
        $recompress = 0;

        if ($this->object->$name) {
            if (is_array($this->object->$name->compress)) {
                foreach ($this->object->$name->compress as $file) {
                    if ($this->groupOne(__DIR__ . $file)) {
                        $recompress++;
                    }
                }
            }
        }

        if ($recompress > 0) {
            $this->concat($name);
        }
    }

    protected function groupOne(string $filePath): bool
    {
        $triggerRecompress = false;

        if (file_exists($filePath)) {
            // if never set we need to set it
            if (!isset($this->hashes[$filePath])) {
                $this->hashes[$filePath] = $this->null;
                $this->contents[$filePath] = $this->null;
            }

            $md5 = md5_file($filePath);

            if ($this->hashes[$filePath] != $md5) {
                $this->hashes[$filePath] = $md5;
                // clear it to trigger a new minify
                $this->contents[$filePath] = $this->null;
                $triggerRecompress = true;
            }
        } else {
            echo 'Can not find "' . $filePath . '".' . PHP_EOL;
        }

        return $triggerRecompress;
    }

    protected function concat(string $compressor): void
    {
        $this->complete = '';

        foreach ($this->object->$compressor->compress as $file) {
            $this->concatOne(__DIR__ . $file, $compressor);
        }

        file_put_contents(__DIR__ . $this->object->$compressor->compressed, $this->complete);
    }

    protected function concatOne(string $filePath, string $compressor)
    {
        if ($this->contents[$filePath] == $this->null) {
            $this->contents[$filePath] = file_get_contents($filePath);

            if (strpos($filePath, '.min.') === false) {
                $start = hrtime(true);
                switch ($compressor) {
                    case 'css':
                        $this->contents[$filePath] = CssMinifer::minify($this->contents[$filePath]);
                        break;
                    case 'js':
                        $this->contents[$filePath] = \JShrink\Minifier::minify($this->contents[$filePath]);
                        break;
                    default:
                        die('unknown compressor type ' . $compressor . PHP_EOL);
                        break;
                }
                $end = hrtime(true);

                $sec = round(($end - $start) / 1000000000, 3);
                $sec = str_pad($sec, 5, ' ', STR_PAD_LEFT);

                echo date('H:i:s ') . $sec . ' ' . $filePath . PHP_EOL;
            }
        }

        $this->complete .= $this->contents[$filePath] . PHP_EOL;
    }
}
