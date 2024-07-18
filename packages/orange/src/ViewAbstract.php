<?php

declare(strict_types=1);

namespace orange\framework;

use Directory;
use Throwable;
use orange\framework\DirectorySearch;
use orange\framework\exceptions\InvalidValue;
use orange\framework\interfaces\DataInterface;
use orange\framework\interfaces\ViewInterface;
use orange\framework\interfaces\RouterInterface;
use orange\framework\exceptions\ResourceNotFound;
use orange\framework\exceptions\view\ViewNotFound;
use orange\framework\exceptions\filesystem\FileNotWritable;
use orange\framework\exceptions\filesystem\DirectoryNotWritable;
use orange\framework\interfaces\DirectorySearchInterface;

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
abstract class ViewAbstract implements ViewInterface
{
    protected ?DataInterface $data;

    // pass thru
    public DirectorySearch $search;

    protected ?RouterInterface $router;

    protected array $config;
    protected array $alias;

    // defaults to sys_get_temp_dir() unless provided via config
    protected string $tempFolder;
    protected bool $debug;
    protected bool $allowDynamicViews;

    protected array $changeable = [
        'tempFolder' => 'is_string',
        'debug' => 'is_bool',
        'allowDynamicViews' => 'is_bool',
    ];

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    protected function __construct(array $config, ?DataInterface $data = null, ?RouterInterface $router = null)
    {
        $this->config = Application::mergeDefaultConfig($config, __DIR__ . '/config/view.php', false);

        $this->data = $data;
        $this->router = $router;

        $this->debug = $this->config['debug'] ?? false;

        $this->allowDynamicViews = $this->config['allow dynamic views'] ?? false;

        $this->tempFolder = rtrim($this->config['temp folder'], DIRECTORY_SEPARATOR);

        if (!is_dir($this->tempFolder)) {
            throw new Directory('Unknown Directory "' . $this->tempFolder . '".');
        }

        $this->alias = $this->config['view aliases'] ?? [];

        // use directory search for view alias
        $this->search = new DirectorySearch([
            // throw exception on missing view
            'quiet' => false,
            'directories' => $this->config['view paths'] + $this->config['default view paths'],
            'match' => '*.' . trim($this->config['extension'], '.'),
            'recursive' => true,
            'lock after scan' => true,
            'normalize keys' => true,
            'resource key style' => 'view',
            'prepend' => true,
        ]);
    }

    public function search(): DirectorySearchInterface
    {
        return $this->search;
    }

    public function addAlias(string $view, string $aliasView): void
    {
        $this->alias[$view] = $aliasView;
    }

    public function render(string $view = '', array $data = []): string
    {
        $view = $this->resolveDynamicView($view);
        $view = $this->resolveAlias($view);

        try {
            $found = $this->search->findFirst($view);
        } catch (ResourceNotFound $e) {
            // convert Resource Not Found into View Not Found Exception
            throw new ViewNotFound($view, 500, $e);
        }

        return $this->generate($found, $this->data($data));
    }

    public function renderString(string $string, array $data = []): string
    {
        $hash = md5($string);

        // use the same file extension as the file based "normal" views
        // because we save this as a file in order to "load" it
        $tempFile = $this->tempFolder . DIRECTORY_SEPARATOR . substr($hash, 0, 6) . DIRECTORY_SEPARATOR . $hash . $this->config['extension'];

        if (!\file_exists($tempFile) || $this->debug === true) {
            // throws error
            $this->isFileWritable($tempFile);

            if (file_put_contents_atomic($tempFile, $string) === false) {
                // didn't write anything?
                throw new FileNotWritable();
            }
        }

        return $this->generate($tempFile, $this->data($data));
    }

    /* change */
    public function change(string $name, mixed $value): self
    {
        if (!isset($this->changeable[$name])) {
            throw new InvalidValue($name);
        }

        // convert 'Shipping Carrier' to 'shippingCarrier'
        $function = str_replace(' ', '', lcfirst(ucwords($this->changeable[$name])));

        if (!$function($value)) {
            throw new InvalidValue($value);
        }

        $this->$name = $value;

        return $this;
    }

    /* protected */

    protected function generate(string $__viewFilePath, array $__dataArray): string
    {
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
                throw new DirectoryNotWritable($dir);
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

    protected function data(array $data): array
    {
        if ($this->data) {
            $data = array_replace((array)$this->data, $data);
        }

        return $data;
    }

    /**
     * if you pass in $c/$m this is converted to controller/method
     * therefore you can make a view path as such /foo/bar/$c/something/$m
     * You can also capture namespaced segements using /foo/bar/$1/$2/$c/$m for example
     */
    protected function resolveDynamicView(string $view): string
    {
        if ($this->allowDynamicViews && $this->router) {
            list($controller, $method) = $this->router->getMatched('callback');

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
                    if (!isset($method)) {
                        throw new InvalidValue('Missing Method and therefore cannot generate dynamic view.');
                    }

                    $view = str_replace('$m', $method, $view);
                }

                // do we need to dynamically add part of the controller name or namespace?
                if (strpos($view, '$') !== false) {
                    // we need the controller to be sent in order to dynamically add it.
                    if (!isset($controller)) {
                        throw new InvalidValue('Missing Controller and therefore cannot generate dynamic view.');
                    }

                    // break up into segments

                    // first normalize it
                    $namespacedController = strtolower($controller);

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
