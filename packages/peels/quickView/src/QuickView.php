<?php

declare(strict_types=1);

namespace peels\quickview;

use dmyers\orange\exceptions\Output as OutputException;
use dmyers\orange\interfaces\OutputInterface;

class QuickView
{
    private static $instance;
    protected OutputInterface $output;
    protected array $quickviews = [];

    public function __construct(array $config, OutputInterface $output)
    {
        $this->quickviews = array_change_key_case($config, CASE_LOWER);

        $this->output = $output;
    }

    public static function getInstance(array $config, OutputInterface $output): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config, $output);
        }

        return self::$instance;
    }

    /**
     * pass thru to output service
     */
    public function __call($name, $arguments)
    {
        if (!method_exists($this->output, $name)) {
            throw new OutputException('Unknown Output Method ' . $name);
        }

        $this->output->$name(...$arguments);
    }

    public function show(string $key, array $data = []): void
    {
        $key = strtolower($key);

        if (!isset($this->quickviews[$key])) {
            throw new OutputException('Unknown Quick View ' . $key);
        }

        $quickviews = array_change_key_case($this->quickviews[$key], CASE_LOWER);

        // redirect sends and exits nothing else will setup
        if (isset($quickviews['redirect'])) {
            $this->output->redirect($quickviews['redirect']);
        }

        if (isset($quickviews['flushall'])) {
            $this->output->flushAll();
        }

        if (isset($quickviews['flushcookies'])) {
            $this->output->flushCookies();
        }

        if (isset($quickviews['flushheaders'])) {
            $this->output->flushHeaders();
        }

        if (isset($quickviews['flush'])) {
            $this->output->flush();
        }

        if (isset($quickviews['contenttype'])) {
            $this->output->contentType($quickviews['contenttype']);
        }

        if (isset($quickviews['charset'])) {
            $this->output->charSet($quickviews['charset']);
        }

        if (isset($quickviews['responsecode'])) {
            $this->output->responseCode($quickviews['responsecode']);
        }

        if (isset($quickviews['header'])) {
            $this->output->header(...$quickviews['header']);
        }

        if (isset($quickviews['headers']) && is_array($quickviews['headers'])) {
            foreach ($quickviews['headers'] as $h) {
                $this->output->header(...$h);
            }
        }

        if (isset($quickviews['cookie'])) {
            $this->output->cookie(...$quickviews['cookie']);
        }

        if (isset($quickviews['cookies']) && is_array($quickviews['cookies'])) {
            foreach ($quickviews['cookies'] as $c) {
                $this->output->cookie(...$c);
            }
        }

        if (isset($quickviews['write'])) {
            $this->output->write($quickviews['write']);
        }

        if (isset($quickviews['template'])) {
            // create tempF file, use it and delete it
            $tempFile = tempnam(sys_get_temp_dir(), 'quickview_');

            file_put_contents($tempFile, $quickviews['template']);

            $this->output->write($this->renderString($tempFile, $data));

            unlink($tempFile);
        }

        if (isset($quickviews['template_file'])) {
            // absolute path to template file
            $this->output->write($this->renderString($quickviews['template_file'], $data));
        }

        if (isset($quickviews['send'])) {
            // send output
            $this->output->send($quickviews['send']);
        }
    }

    // function protected variable scope
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
