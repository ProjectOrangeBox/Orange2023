<?php

declare(strict_types=1);

namespace orange\framework;

use Directory;
use Throwable;
use orange\framework\Singleton;
use orange\framework\exceptions\InvalidValue;
use orange\framework\exceptions\ViewNotFound;
use orange\framework\interfaces\DataInterface;
use orange\framework\interfaces\ViewInterface;
use orange\framework\exceptions\FileNotWritable;
use orange\framework\exceptions\FolderNotWritable;
use orange\framework\exceptions\InvalidConfigurationValue;

/**
 * This should be extended by viewer classes
 *
 * The default View.php is a simple PHP based view rendering engine
 *
 * other could be for example handlebars, markdown, twig, mailmerge
 * but as long as they follow the ViewInterface
 * all of the methods stay the same to use a view
 * this has also been made very generic to try and support multiple view engines
 *
 */
abstract class ViewAbstract extends Singleton implements ViewInterface
{
    private static ?ViewInterface $instance = null;
    protected DataInterface $data;
    public DirectorySearch $viewSearch;

    protected array $config;
    protected array $alias;

    // defaults to sys_get_temp_dir() unless provided via config
    protected string $tempFolder;
    protected bool $debug = false;
    protected bool $allowDynamicViews = true;

    protected array $changeable = [
        'tempFolder' => 'is_string',
        'debug' => 'is_bool',
        'allowDynamicViews' => 'is_bool',
    ];

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    protected function __construct(array $config, ?DataInterface $data = null)
    {
        $this->config = mergeDefaultConfig($config, __DIR__ . '/config/view.php', false);

        $this->data = $data;

        $this->tempFolder = rtrim($this->config['temp folder'], DIRECTORY_SEPARATOR);

        if (!is_dir($this->tempFolder)) {
            throw new Directory('Unknown Directory "' . $this->tempFolder . '".');
        }

        $this->debug = $this->config['debug'] ?? $this->debug;

        $this->alias = $this->config['view aliases'] ?? [];

        // use directory search for view alias
        $this->viewSearch = new DirectorySearch([
            'directories' => $this->config['default view paths'] + $this->config['view paths'],
            'extension' => $this->config['extension'],
        ]);
    }

    public static function getInstance(array $config, ?DataInterface $data = null): self
    {
        if (self::$instance === null) {
            self::$instance = new (get_called_class())($config, $data);
        }

        return self::$instance;
    }

    public function addAlias(string $view, string $aliasView): void
    {
        $this->alias[$view] = $aliasView;
    }

    public function render(string $view = '', array $data = []): string
    {
        $view = $this->resolveDynamicView($view);
        $view = $this->resolveAlias($view);

        // throws exception if not found
        return $this->generate($this->viewSearch->find($view), $data);
    }

    public function renderString(string $string, array $data = []): string
    {
        $hash = md5($string);

        // use the same file extension as the file based "normal" views
        // because we save this as a file in order to "load" it
        $tempFile = $this->tempFolder . DIRECTORY_SEPARATOR . substr($hash, 0, 6) . DIRECTORY_SEPARATOR . $hash . $this->viewSearch->extension();

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

    /* change */
    public function change(string $name, mixed $value): self
    {
        if (!isset($this->changeable[$name])) {
            throw new InvalidValue($name);
        }

        $function = $this->changeable[$name];

        if (!$function($value)) {
            throw new InvalidValue($value);
        }

        $this->$name = $value;

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

    protected function resolveAlias(string $view): string
    {
        return $this->alias[$view] ?? $view;
    }

    /**
     * if you pass in $c/$m this is converted to controller/method
     * therefore you can make a view path as such /foo/bar/$c/something/$m
     * You can also capture namespaced segements using /foo/bar/$1/$2/$c/$m for example
     */
    protected function resolveDynamicView(string $view): string
    {
        if ($this->allowDynamicViews) {
            if (strpos($view, '$') !== false || strpos($view, '*') !== false || $view === '') {
                if ($view == '') {
                    $view = '$c/$m';
                } elseif (str_ends_with($view, '*/*')) {
                    $view = substr($view, 0, -3) . '$c/$m';
                }

                if (str_ends_with($view, '/*')) {
                    $view = substr($view, 0, -2) . '/$m';
                }

                // do we need to dynamically add the method which is always $m?
                if (strpos($view, '$m') !== false) {
                    // we need the method to be sent in order to dynamically add it.
                    if (!isset($this->config['method'])) {
                        throw new InvalidConfigurationValue('Missing Method and therefore cannot generate dynamic view.');
                    }

                    $view = str_replace('$m', $this->config['method'], $view);
                }

                // do we need to dynamically add part of the controller name or namespace?
                if (strpos($view, '$') !== false) {
                    // we need the controller to be sent in order to dynamically add it.
                    if (!isset($this->config['controller'])) {
                        throw new InvalidConfigurationValue('Missing Controller and therefore cannot generate dynamic view.');
                    }

                    // break up into segments

                    // first normalize it
                    $namespacedController = strtolower($this->config['controller']);

                    // does it end in "controller" if so remove it
                    if (str_ends_with($namespacedController, 'controller')) {
                        $namespacedController = substr($namespacedController, 0, -10);
                    }

                    // flip namespace \ to / then break into segments
                    foreach (explode('/', str_replace('\\', '/', $namespacedController)) as $index => $segment) {
                        $view = str_replace('$' . ($index + 1), $segment, $view);
                        // capture the value (will contain the last value when the foreach loop is finished)
                        $controllerName = $segment;
                    }

                    // the last value is the controller
                    $view = str_replace('$c', $controllerName, $view);
                }
            }
        }

        return $view;
    }
}
