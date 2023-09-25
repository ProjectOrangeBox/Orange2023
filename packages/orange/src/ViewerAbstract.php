<?php

declare(strict_types=1);

namespace dmyers\orange;

use Throwable;
use dmyers\orange\exceptions\ViewNotFound;
use dmyers\orange\interfaces\DataInterface;
use dmyers\orange\exceptions\FileNotWritable;
use dmyers\orange\interfaces\ViewerInterface;
use dmyers\orange\exceptions\FolderNotWritable;
use dmyers\orange\exceptions\InvalidValue;
use peel\validate\exceptions\InvalidValue as ExceptionsInvalidValue;

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
    private static ViewerInterface $instance;
    protected DataInterface $data;

    protected array $config = [];
    protected array $viewPaths = [];
    protected array $loadedViews = [];
    protected array $aliasViews = [];
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
    protected bool $allowPhp = false;

    public function __construct(array $config, ?DataInterface $data = null)
    {
        $this->config = mergeDefaultConfig($config, __DIR__ . '/config/view.php');

        $this->data = $data;

        if (isset($this->config['temp folder'])) {
            $this->tempFolder = rtrim($this->config['temp folder'], '/');
        }

        $this->debug = $this->config['debug'] ?? $this->debug;
        $this->extension = $this->config['extension'] ?? '.' . trim($this->extension, '.');
        $this->aliasViews = $this->config['alias view'] ?? $this->aliasViews;
        $this->allowPhp = $this->config['allow PHP'] ?? $this->allowPhp;

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

    public function changeOption(string $name, mixed $value): self
    {
        // extended in your view classes as needed then call parent::changeOption(...)
        switch ($name) {
            case 'views':
                $this->loadedViews = $this->validateArgument($value,'is_array');
                break;
            case 'plugins':
                $this->loadedPlugins = $this->validateArgument($value,'is_array');
                break;
            case 'debug':
                $this->debug = $this->validateArgument($value,'is_bool');
                break;
            case 'extension':
                $this->extension = $this->validateArgument($value,'is_string');
                break;
            case 'delimiters':
                $value = $this->validateArgument($value,'is_array');

                $this->delimiters = [$value[0], $value[1]];

                $this->l_delim = $this->delimiters[0];
                $this->r_delim = $this->delimiters[1];
                break;
            case 'view paths':
                $this->viewPaths = $this->validateArgument($value,'is_array');
                break;
            case 'plugin paths':
                $this->plugins = $this->validateArgument($value,'is_array');
                break;
            case 'temp folder':
                $this->tempFolder = $this->validateArgument($value,'is_string');
                break;
            case 'alias views':
                $this->aliasViews = $this->validateArgument($value,'is_array');
                break;
            default:
                throw new InvalidValue('Unknown value "' . $name . '".');
        }

        return $this;
    }

    public function render(string $view, array $data = []): string
    {
        $view = (isset($this->aliasViews[$view])) ? $this->aliasViews[$view] : $view;

        return $this->generate($this->findView($view), $data);
    }

    public function renderString(string $string, array $data = []): string
    {
        $hash = md5($string);

        $tempFile = $this->tempFolder . '/' . substr($hash, 0, 6) . '/' . $hash . $this->extension;

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
            'alias views' => $this->aliasViews,
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

    protected function validateArgument($value, $function)
    {
        $typeMap = [
            'is_string' => 'string',
            'is_int' => 'integer',
            'is_float' => 'floating',
            'is_bool' => 'boolean',
            'is_array' => 'array',
        ];

        if (!$function($value)) {
            throw new InvalidValue('Must be a ' . $typeMap[$function] . ' value.');
        }

        return $value;
    }
}
