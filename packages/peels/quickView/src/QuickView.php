<?php

declare(strict_types=1);

namespace peels\quickview;

use dmyers\orange\interfaces\OutputInterface;
use dmyers\orange\exceptions\Output as OutputException;

class QuickView
{
    private static QuickView $instance;

    protected array $config = [];
    protected OutputInterface $output;

    public function __construct(array $config, OutputInterface $output)
    {
        $this->config = $config;
        $this->output = $output;
    }

    public static function getInstance(array $config, OutputInterface $output): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config, $output);
        }

        return self::$instance;
    }

    public function load(string $key, array $data = []): self
    {
        if (!isset($this->config[$key])) {
            throw new OutputException('Unknown Quick View ' . $key);
        }

        $predefined = array_change_key_case($this->config[$key], CASE_LOWER);

        // redirect sends and exits nothing else will setup
        if (isset($predefined['redirect'])) {
            $this->output->redirect($predefined['redirect']);
        }

        if (isset($predefined['flushall'])) {
            $this->output->flushAll();
        }

        if (isset($predefined['flushcookies'])) {
            $this->output->flushCookies();
        }

        if (isset($predefined['flushheaders'])) {
            $this->output->flushHeaders();
        }

        if (isset($predefined['flush'])) {
            $this->output->flush();
        }

        if (isset($predefined['contenttype'])) {
            $this->output->contentType($predefined['contenttype']);
        }

        if (isset($predefined['charset'])) {
            $this->output->charSet($predefined['charset']);
        }

        if (isset($predefined['responsecode'])) {
            $this->output->responseCode($predefined['responsecode']);
        }

        if (isset($predefined['header'])) {
            if (is_array($predefined['header'])) {
                foreach ($predefined['header'] as $h) {
                    $this->output->header($h);
                }
            } else {
                $this->output->header($predefined['header']);
            }
        }

        if (isset($predefined['cookie'])) {
            if (is_array($predefined['cookie'])) {
                foreach ($predefined['cookie'] as $h) {
                    $this->output->cookie(...$h);
                }
            } else {
                $this->output->cookie(...$predefined['cookie']);
            }
        }

        if (isset($predefined['write'])) {
            $this->output->write($predefined['write']);
        }

        if (isset($predefined['template'])) {
            // create tempF file, use it and delete it
            $tempFile = tempnam(sys_get_temp_dir(), 'quickview_');

            file_put_contents($tempFile, $predefined['template']);

            $this->output->write($this->renderString($tempFile, $data));

            unlink($tempFile);
        }

        if (isset($predefined['send'])) {
            $this->output->send();
        }

        return $this;
    }

    public function __call($name, $arguments)
    {
        if (!method_exists($this->output, $name)) {
            throw new OutputException('Unknown Output Method ' . $name);
        }

        $this->output->$name(...$arguments);
    }

    protected function renderString(string $__viewFilePath, array $__dataArray): string
    {
        // extract out view data and make it in scope
        extract((array)$__dataArray, \EXTR_OVERWRITE);

        // start output cache
        ob_start();

        // load in view (which now has access to the in scope view data
        require $__viewFilePath;

        // capture cache and return
        return ob_get_clean();
    }
}
