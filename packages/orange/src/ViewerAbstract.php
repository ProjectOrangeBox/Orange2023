<?php

declare(strict_types=1);

namespace dmyers\orange;

use Throwable;
use dmyers\orange\exceptions\ViewNotFound;
use dmyers\orange\interfaces\DataInterface;
use dmyers\orange\exceptions\FileNotWritable;
use dmyers\orange\interfaces\ViewerInterface;
use dmyers\orange\exceptions\FolderNotWritable;

/**
 * This should be extended by viewer classes
 *
 * The default View.php is a simple PHP based view rendering engine
 *
 * other could be for example handlebars, markdown, twig, mailmerge
 * but as long as they follow the ViewerInterface
 * all of the methods stay the same to use a view
 * this has also been made very generic to try and support multiple view engines
 *
 */
abstract class ViewerAbstract implements ViewerInterface
{
    protected static ViewerInterface $instance;
    protected DataInterface $data;
    protected array $config = [];
    protected array $viewPaths = [];
    protected array $loadedViews = [];
    protected array $aliasesViews = [];
    protected string $extension = '.php';
    protected string $foundView = '';
    // defaults to sys_get_temp_dir() unless provided via config
    protected string $tempFolder = '';
    protected bool $debug = false;
    protected array $plugins = [];
    protected array $loadedPlugins = [];
    protected array $delimiters = [];
    protected string $l_delim = '';
    protected string $r_delim = '';

    public function __construct(array $config, ?DataInterface $data = null)
    {
        $this->config = mergeDefaultConfig($config, __DIR__ . '/config/view.php');

        $this->data = $data;

        if (isset($this->config['temp folder'])) {
            $this->tempFolder = rtrim($this->config['temp folder'], '/');
        }

        $this->debug = $this->config['debug'] ?? $this->debug;
        $this->extension = $this->config['extension'] ?? $this->extension;
        $this->aliasesViews = $this->config['aliases view'] ?? $this->aliasesViews;

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

    public static function getInstance(array $config, ?DataInterface $data = null): self
    {
        if (!isset(self::$instance)) {
            $extendingClass = get_called_class();

            self::$instance = new $extendingClass($config, $data);
        }

        return self::$instance;
    }

    public function render(string $view, array $data = []): string
    {
        $view = (isset($this->aliasesViews[$view])) ? $this->aliasesViews[$view] : $view;

        return $this->generate($this->findView($view), $data);
    }

    public function renderString(string $string, array $data = []): string
    {
        $hash = md5($string);
        
        $tempFile = $this->tempFolder . '/' . substr($hash,0,6). '/'. $hash . $this->extension;

        if (!\file_exists($tempFile) || $this->debug === true) {
            // throws error
            $this->isFileWritable($tempFile);

            if (file_put_contents_atomic($tempFile, $string) === false) {
                // didn't write anything?
                throw new FileNotWritable();
            }
        }

        return $this->generate($tempFile, $data);
    }

    public function addPath(string $path, bool $first = false): self
    {
        // path is added without checking if it's there for various reasons
        $path = rtrim($path, '/');

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

    /**
     * if you are caching the entire array on a production system for example
     * use this to inject the entire array
     */
    public function setViews(array $loadedViews): self
    {
        $this->loadedViews = $loadedViews;

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

    /**
     * if you are caching the entire array on a production system for example
     * use this to inject the entire array
     */
    public function setPlugins(array $loadedPlugins): self
    {
        $this->loadedPlugins = $loadedPlugins;

        return $this;
    }

    public function findView(string $view): string
    {
        if (!isset($this->loadedViews[$view])) {
            foreach ($this->viewPaths as $path) {
                $fullpath = rtrim($path, '/') . '/' . ltrim($view, '/') . $this->extension;

                if (file_exists($fullpath)) {
                    $this->loadedViews[$view] = $fullpath;

                    break;
                }
            }

            // was it loaded?
            if (!isset($this->loadedViews[$view])) {
                throw new ViewNotFound('View "' . $view . '" Extension "' . $this->extension . '" Not Found.');
            }
        }

        return $this->loadedViews[$view];
    }

    public function viewExists(string $view): bool
    {
        try {
            $this->findView($view);

            // if it didn't throw an exception it exists
            $exists = true;
        } catch (Throwable $e) {
            $exists = false;
        }

        return $exists;
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

    public function __debugInfo(): array
    {
        return [
            'config' => $this->config,
            'view paths' => $this->viewPaths,
            'loaded views' => $this->loadedViews,
            'aliases views' => $this->aliasesViews,
            'extension' => $this->extension,
            'found view' => $this->foundView,
            'temp folder' => $this->tempFolder,
            'debug' => $this->debug,
            'plugins' => $this->plugins,
            'delimiters' => $this->delimiters,
            'l_delim' => $this->l_delim,
            'r_delim' => $this->r_delim,
            'loaded plugins' => $this->loadedPlugins,
        ];
    }

    protected function isFileWritable(string $file): bool
    {
        // check we can write in the directory
        $dir = dirname($file);

        if (!file_exists($dir)) {
            try {
                // directory, permissions, recursive
                mkdir($dir, 0777, true);
            } catch (Throwable $e) {
                throw new FolderNotWritable($dir);
            }
        }

        if (!is_writable($dir)) {
            throw new FileNotWritable($dir);
        }

        return true;
    }
}
