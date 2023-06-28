<?php

declare(strict_types=1);

namespace dmyers\orange;

use dmyers\orange\exceptions\ViewNotFound;
use dmyers\orange\interfaces\DataInterface;
use dmyers\orange\exceptions\FileNotWritable;
use dmyers\orange\exceptions\FolderNotFound;
use dmyers\orange\interfaces\ViewerInterface;

abstract class ViewerAbstract implements ViewerInterface
{
    protected DataInterface $data;
    protected array $config = [];
    protected array $viewPaths = [];
    protected string $extension = '.php';
    protected string $foundView = '';
    protected string $tempFolder = '';
    protected bool $debug = false;
    protected array $plugins = [];
    protected array $delimiters = [];
    protected string $l_delim = '';
    protected string $r_delim = '';

    public function render(string $view, array $data = []): string
    {
        $view = (isset($this->config['view aliases'][$view])) ? $this->config['view aliases'][$view] : $view;

        return $this->generate($this->findView($view), $data);
    }

    public function renderString(string $string, array $data = []): string
    {
        $tempFile = $this->tempFolder . '/' . md5($string) . $this->extension;

        if (!\file_exists($tempFile) || $this->debug === true) {
            if ($this->file_put_contents_atomic($tempFile, $string) === false) {
                throw new FileNotWritable();
            }
        }

        return $this->generate($tempFile, $data);
    }

    public function addPath(string $path, bool $first = false): self
    {
        $path = rtrim($path, '/');

        if (!\realpath($path)) {
            throw new FolderNotFound($path);
        }

        if ($first) {
            array_unshift($this->viewPaths, rtrim($path, '/'));
        } else {
            $this->viewPaths[] = rtrim($path, '/');
        }

        return $this;
    }

    public function addPaths(array $paths): self
    {
        foreach ($paths as $path) {
            $this->addPath($path);
        }

        return $this;
    }

    public function addPlugin(string $name, mixed $args): self
    {
        $this->plugins[$name] = $args;

        return $this;
    }

    public function addPlugins(array $plugins): self
    {
        foreach ($plugins as $name => $args) {
            $this->addPlugin($name, $args);
        }

        return $this;
    }

    /* protected */

    protected function generate(string $__viewFilePath, array $__dataArray): string
    {
        if (isset($this->data)) {
            // convert ArrayObject into Array
            $__dataArray = \array_replace_recursive((array)$this->data, $__dataArray);
        }

        // what file are we looking for?
        if (!\file_exists($__viewFilePath)) {
            throw new ViewNotFound('View "' . $__viewFilePath . '" Not Found.');
        }

        // extract out view data and make it in scope
        extract((array)$__dataArray, \EXTR_OVERWRITE);

        // start output cache
        ob_start();

        // load in view (which now has access to the in scope view data
        require $__viewFilePath;

        // capture cache and return
        return ob_get_clean();
    }

    protected function file_put_contents_atomic(string $filePath, string $content, int $flags = 0, $context = null): int|false
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

        return $strlen;
    }

    protected function findView(string $view): string
    {
        if (!$this->viewExists($view)) {
            throw new ViewNotFound('View "' . $view . '" Extension "' . $this->extension . '" Not Found.');
        }

        return $this->foundView;
    }

    protected function viewExists(string $view): bool
    {
        $this->foundView = '';

        foreach ($this->viewPaths as $path) {
            $file = $path . '/' . ltrim($view, '/') . $this->extension;

            if (\file_exists($file)) {
                $this->foundView = $file;

                break;
            }
        }

        return !empty($this->foundView);
    }

    protected function setConfiguration(): void
    {
        if (isset($this->config['temp folder'])) {
            $this->tempFolder = rtrim($this->config['temp folder'], '/');
        }

        if (isset($this->config['debug'])) {
            $this->debug = $this->config['debug'];
        }

        if (isset($this->config['extension'])) {
            $this->extension = $this->config['extension'];
        }

        if (isset($this->config['delimiters'])) {
            $this->delimiters = [$this->config['delimiters'][0], $this->config['delimiters'][1]];

            $this->l_delim = $this->delimiters[0];
            $this->r_delim = $this->delimiters[1];
        }

        if (isset($this->config['view paths'])) {
            $this->addPaths($this->config['view paths']);
        }

        if (isset($this->config['plugins'])) {
            $this->addPlugins($this->config['plugins']);
        }
    }
}
