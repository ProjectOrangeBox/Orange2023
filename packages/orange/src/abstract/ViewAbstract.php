<?php

declare(strict_types=1);

namespace orange\framework\abstract;

use Directory;
use Throwable;
use orange\framework\base\Singleton;
use orange\framework\exceptions\InvalidValue;
use orange\framework\helpers\DirectorySearch;
use orange\framework\interfaces\DataInterface;
use orange\framework\interfaces\ViewInterface;
use orange\framework\traits\ConfigurationTrait;
use orange\framework\interfaces\RouterInterface;
use orange\framework\exceptions\ResourceNotFound;
use orange\framework\exceptions\view\ViewNotFound;
use orange\framework\interfaces\DirectorySearchInterface;
use orange\framework\exceptions\filesystem\FileNotWritable;
use orange\framework\exceptions\filesystem\DirectoryNotWritable;
use orange\framework\exceptions\IncorrectInterface;

/**
 * Abstract class for implementing view rendering engines.
 *
 * This class serves as a base for various view engines like PHP views, Twig, Markdown, etc.
 * All engines must implement ViewInterface, ensuring uniformity across different rendering engines.
 */
abstract class ViewAbstract extends Singleton implements ViewInterface
{
    /** include ConfigurationTrait methods */
    use ConfigurationTrait;

    /**
     * View file search utility
     */
    public DirectorySearch $search;

    /**
     * Data source for the view
     */
    protected ?DataInterface $data;

    /**
     * Router instance for dynamic view resolution
     */
    protected RouterInterface $router;

    /**
     * Debug mode toggle
     */
    protected bool $debug = false;

    /**
     * Allow dynamic views toggle
     */
    protected bool $allowDynamicViews = false;

    /**
     * Temporary directory for cached view files
     */
    protected string $tempDirectory = '';

    /**
     * Aliases for view names
     */
    protected array $alias = [];

    /**
     * Number of characters for sub-directory path hashing
     */
    protected int $subPathSize = 6;

    /**
     * Validations for changeable properties
     */
    protected array $changeableTypeCheck = [
        'tempDirectory' => 'is_string',
        'debug' => 'is_bool',
        'allowDynamicViews' => 'is_bool',
    ];

    /**
     * Constructor is protected to enforce Singleton pattern.
     * Use Singleton::getInstance() to create an instance.
     *
     * @param array $config Configuration array.
     * @param DataInterface|null $data Optional data source for the view.
     * @throws IncorrectInterface|Directory
     */
    protected function __construct(array $config, ?DataInterface $data = null)
    {
        logMsg('INFO', __METHOD__);

        $this->config = $this->mergeWithDefault($config, false);

        if ($data) {
            $this->data = $data;
        }

        if (isset($config['router'])) {
            if (!$config['router'] instanceof RouterInterface) {
                throw new IncorrectInterface('RouterInterface');
            }
            $this->router = $config['router'];
        }

        $this->debug = $this->config['debug'];
        $this->allowDynamicViews = $this->config['allow dynamic views'];
        $this->tempDirectory = rtrim($this->config['temp directory'], DIRECTORY_SEPARATOR);

        if (!is_dir($this->tempDirectory)) {
            throw new Directory('Unknown Directory "' . $this->tempDirectory . '".');
        }

        $this->alias = $this->config['view aliases'];
        $this->subPathSize = $this->config['sub path size'];

        // Initialize DirectorySearch for locating views
        $this->search = new DirectorySearch([
            'quiet' => false,
            'directories' => $this->config['view paths'] + $this->config['default view paths'],
            'match' => '*.' . trim($this->config['extension'], '.'),
            'recursive' => true,
            'lock after scan' => false,
            'normalize keys' => true,
            'resource key style' => 'view',
            'pend' => DirectorySearchInterface::PREPEND,
        ]);
    }

    /**
     * Returns the search utility.
     *
     * @return DirectorySearchInterface
     */
    public function search(): DirectorySearchInterface
    {
        logMsg('INFO', __METHOD__);
        return $this->search;
    }

    /**
     * Add an alias for a view.
     *
     * @param string $view Original view name.
     * @param string $aliasView Alias name.
     */
    public function addAlias(string $view, string $aliasView): void
    {
        logMsg('INFO', __METHOD__ . ' ' . $view . ' ' . $aliasView);
        $this->alias[$view] = $aliasView;
    }

    /**
     * Render a view file.
     *
     * @param string $view View name or path.
     * @param array $data Data to pass into the view.
     * @param array $options Rendering options.
     * @return string Rendered view content.
     * @throws ViewNotFound|ResourceNotFound
     */
    public function render(string $view = '', array $data = [], array $options = []): string
    {
        logMsg('INFO', __METHOD__);
        logMsg('DEBUG', '', ['view' => $view, 'data' => $data, 'options' => $options]);


        // allow dynamic views only if router ALSO provided
        if ($this->allowDynamicViews && isset($this->router)) {
            $view = $this->resolveDynamicView($view);
        }

        $view = $this->resolveAlias($view);

        try {
            $found = $this->search->findFirst($view);
        } catch (ResourceNotFound $e) {
            // convert Resource Not Found into View Not Found Exception
            // because the resource is a view when used in this context
            throw new ViewNotFound($view, 500, $e);
        }

        // generate the view based on the found view file
        return $this->generate($found, $this->data($data));
    }

    /**
     * Render a view from a string.
     *
     * @param string $string Template content.
     * @param array $data Data for the template.
     * @param array $options Rendering options.
     * @return string Rendered output.
     * @throws FileNotWritable
     */
    public function renderString(string $string, array $data = [], array $options = []): string
    {
        logMsg('INFO', __METHOD__);
        logMsg('DEBUG', '', ['string' => $string, 'data' => $data, 'options' => $options]);

        // convert the view into a unique hash
        // and make sure it's not binary value!
        $filename = sha1($string, false);

        // are we putting the template file in a sub directory?
        // this is usually a good idea so your OS doesn't have a directory with 10,000 files in it
        $subPath = ($this->subPathSize > 0) ? DIRECTORY_SEPARATOR . substr($filename, 0, $this->subPathSize) : '';

        // use the same file extension as the file based "normal" views
        // because we save this as a file in order to "load" it
        $templatePath = $this->tempDirectory . $subPath . DIRECTORY_SEPARATOR . $filename . $this->config['extension'];

        // if the file doesn't exist and debug is not true
        if (!\file_exists($templatePath) || $this->debug === true) {
            // throws error
            $this->isFileWritable($templatePath);

            // write the file in a way to not run into
            // somebody else writing the same file at the same time
            if (file_put_contents_atomic($templatePath, $string) === false) {
                // didn't write anything?
                throw new FileNotWritable();
            }
        }

        return $this->generate($templatePath, $this->data($data));
    }

    /**
     * Change a configurable property.
     *
     * @param string $name Property name.
     * @param mixed $value New value.
     * @return self
     * @throws InvalidValue
     */
    public function change(string $name, mixed $value): self
    {
        logMsg('INFO', __METHOD__);
        logMsg('DEBUG', '', ['name' => $name, 'value' => $value]);

        if (!isset($this->changeableTypeCheck[$name])) {
            throw new InvalidValue($name);
        }

        // convert 'Shipping Carrier' to 'shippingCarrier'
        $typeCheckFunction = $this->changeableTypeCheck[$name];
        if (!$typeCheckFunction($value)) {
            throw new InvalidValue($value . ' is not ' . $typeCheckFunction);
        }

        // convert a human readable name to a variable name
        $variableName = str_replace(' ', '', lcfirst(ucwords($name)));

        // set value
        $this->$variableName = $value;

        return $this;
    }

    /**
     * Generate the final rendered view content.
     *
     * @param string $__viewFilePath File path to the view.
     * @param array $__dataArray Data for rendering.
     * @return string Rendered output.
     */
    protected function generate(string $__viewFilePath, array $__dataArray): string
    {
        logMsg('INFO', __METHOD__ . ' ' . $__viewFilePath);
        logMsg('DEBUG', '', ['__viewFilePath' => $__viewFilePath, '__dataArray' => $__dataArray]);

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

    /**
     * Check if a file is writable, and if not, attempt to make its directory writable.
     *
     * @param string $file The file path to check.
     * @return bool Returns true if the file or directory is writable.
     * @throws DirectoryNotWritable If the directory cannot be created or is not writable.
     * @throws FileNotWritable If the file cannot be written to.
     */
    protected function isFileWritable(string $file): bool
    {
        // Get the directory of the file
        $dir = dirname($file);

        // If the directory doesn't exist, attempt to create it
        if (!file_exists($dir)) {
            try {
                // Create the directory recursively with permissions
                mkdir($dir, 0777, true);
            } catch (Throwable $e) {
                throw new DirectoryNotWritable($dir);
            }
        }

        // Check if the directory is writable
        if (!is_writable($dir)) {
            throw new FileNotWritable($dir);
        }

        return true;
    }

    /**
     * Resolve an alias to its mapped view path if an alias exists.
     *
     * @param string $view The original view name.
     * @return string The resolved view name after alias mapping.
     */
    protected function resolveAlias(string $view): string
    {
        // Check if an alias exists for the given view
        $alias = $this->alias[$view] ?? $view;

        logMsg('INFO', __METHOD__ . ' ' . $view . ' ' . $alias);

        return $alias;
    }

    /**
     * Merge incoming data with the view's existing data source, if available.
     *
     * @param array $data Incoming data array for the view.
     * @return array The merged data array.
     */
    protected function data(array $data): array
    {
        // If view-level data is set, merge it with the provided data
        if ($this->data) {
            $data = array_replace((array)$this->data, $data);
        }

        // Ensure the result is an array, not a Data Object
        return $data;
    }

    /**
     * Resolve dynamic view paths based on router callback information.
     *
     * Dynamic placeholders in the view string (e.g., $c, $m, $1, $2) are replaced with
     * controller, method, or namespace segments dynamically.
     *
     * @param string $view The view string with possible dynamic placeholders.
     * @return string The dynamically resolved view string.
     * @throws InvalidValue If controller or method is missing while resolving placeholders.
     */
    protected function resolveDynamicView(string $view): string
    {
        logMsg('INFO', __METHOD__ . ' argument: "' . $view . '"');

        // Define dynamic placeholders
        $prefix = '$';
        $controllerString = $prefix . 'c';
        $methodString = $prefix . 'm';

        // Retrieve controller and method from the router's matched callback
        list($controller, $method) = $this->router->getMatched('callback');

        // Check if placeholders exist or if the view string is dynamic
        if (strpos($view, $prefix) !== false || strpos($view, '*') !== false || $view === '') {
            // Handle default controller and method placeholders
            if ($view == '') {
                $view = $controllerString . '/' . $methodString;
            } elseif (str_ends_with($view, '*/*')) {
                $view = substr($view, 0, -3) . $controllerString . '/' . $methodString;
            }

            if (str_ends_with($view, '/*')) {
                $view = substr($view, 0, -2) . '/' . $methodString;
            }

            // Replace method placeholder
            if (strpos($view, $methodString) !== false) {
                if (!isset($method)) {
                    throw new InvalidValue('Missing Method and therefore cannot generate dynamic view.');
                }
                $view = str_replace($methodString, $method, $view);
            }

            // Replace controller placeholder and namespace segments
            if (strpos($view, $prefix) !== false) {
                if (!isset($controller)) {
                    throw new InvalidValue('Missing Controller and therefore cannot generate dynamic view.');
                }

                // Normalize the controller string
                $namespacedController = strtolower($controller);

                // Remove "controller" suffix if it exists
                if (str_ends_with($namespacedController, 'controller')) {
                    $namespacedController = substr($namespacedController, 0, -10);
                }

                // Break controller namespace into segments
                foreach (explode('/', str_replace('\\', '/', $namespacedController)) as $index => $segment) {
                    $view = str_replace($prefix . ($index + 1), $segment, $view);

                    // Store the last segment
                    $controllerName = $segment;
                }

                // Replace the controller placeholder with the final segment
                $view = str_replace($controllerString, $controllerName, $view);
            }
        }

        logMsg('INFO', __METHOD__ . ' return: "' . $view . '"');

        return $view;
    }
}
