<?php

declare(strict_types=1);

namespace orange\framework;

use Throwable;
use orange\framework\base\Singleton;
use orange\framework\helpers\DirectorySearch;
use orange\framework\interfaces\DataInterface;
use orange\framework\interfaces\ViewInterface;
use orange\framework\interfaces\InputInterface;
use orange\framework\traits\ConfigurationTrait;
use orange\framework\interfaces\OutputInterface;

/**
 * Class Error
 *
 * Manages error handling, rendering error views, and sending appropriate outputs.
 * Implements the Singleton pattern for consistent usage across the application.
 * Use Singleton::getInstance() to obtain an instance.
 *
 * @package orange\framework
 */
class Error extends Singleton
{
    use ConfigurationTrait;

    /** @var DataInterface Data service instance */
    public DataInterface $data;

    /** @var InputInterface Input service instance */
    public InputInterface $input;

    /** @var ViewInterface View service instance */
    public ViewInterface $view;

    /** @var OutputInterface Output service instance */
    public OutputInterface $output;

    /** @var int Default error code */
    public int $code = 500;

    /** @var int HTTP status code */
    public int $httpCode = 0;

    /** @var string Request type (e.g., 'cli', 'html', 'ajax') */
    public string $requestType = '';

    /** @var string Directory for error views */
    public string $errorViewDirectory = '';

    /** @var string Directory for environment-specific views */
    public string $envDirectory = '';

    /** @var string Directory for request type-specific views */
    public string $requestTypeDirectory = '';

    /** @var string The view file used for rendering */
    public string $viewFile = '';

    /** @var string Content to be sent as output */
    public string $outputContent = '';

    /**
     * Constructor
     *
     * Initializes the Error class with the given configuration and optional exception.
     *
     * @param array $config Configuration options.
     * @param Throwable|null $thrown Optional exception causing the error.
     */
    protected function __construct(array $config = [], ?Throwable $thrown = null)
    {
        logMsg('INFO', __METHOD__);

        // merge defaults with passed in config
        $this->config = $this->mergeWithDefault($config);

        // try to setup our services
        // these are loaded from the service container or
        // if it's not loaded we manually load the orange ones
        $this->data = $this->getService('data', []);
        $this->input = $this->getService('input', [[]]);
        $this->view = $this->getService('view', [[], $this->data]);
        $this->output = $this->getService('output', [[],$this->input]);

        // base view directory to search for error views
        $this->errorViewDirectory = $this->config['error view directory'];

        // assume worst case it's production - also make lowercase because we use this as a directory in the path
        $this->envDirectory = defined('ENVIRONMENT') ? strtolower(ENVIRONMENT) : 'production';

        // let's try to determine the output type
        // the output class will auto convert this to a mime type for output
        // html, ajax, cli
        // request type as lowercase (true)
        $this->requestType = $this->input->requestType(true);

        // Use this as a directory when looking for an error view file
        $this->requestTypeDirectory = $this->requestType;

        // do we have a exception attached?
        if ($thrown) {
            // if an exception is attached then an exception instanced this object
            // so grab the code and message
            $this->data->merge([
                'message' => $thrown->getMessage(),
                'code' => $thrown->getCode(),
                'options' => $thrown->getTrace(),
                'line' => $thrown->getLine(),
                'file' => $thrown->getFile(),
            ]);

            // if the thrown exceptions error code
            // if great than 0 then use that as the code
            if ($thrown->getCode() > 0) {
                $this->code = $thrown->getCode();
            }

            // if the thrown exception has the method getHttpCode
            // then call it and put it's output in httpCode
            if (method_exists($thrown, 'getHttpCode')) {
                 /** @disregard */
                $this->httpCode = $thrown->getHttpCode();
            }

            // if the thrown exception has the method getOutput
            // then call it and write it's output in output
            if (method_exists($thrown, 'getOutput')) {
                 /** @disregard */
                 $this->outputContent = $thrown->getOutput();
            }

            // if the thrown exception has the method decorate
            // allow the exception the chance to "decorate" the error class
            // this is a catch all incase getHttpCode & getOutput aren't enough
            if (method_exists($thrown, 'decorate')) {
                 /** @disregard */
                 $thrown->decorate($this);
            }
        }

        // if no output content set up by $thrown
        // then try to figure out a viewFile
        if (empty($this->outputContent) && empty($this->viewFile)) {
            $this->outputContent = $this->renderViewBasedOnCode($this->code, $this->httpCode);
        }

        // if a view file is setup by $thrown or from renderViewBasedOnCode use that
        if (!empty($this->viewFile)) {
            $this->outputContent = $this->view->render($this->viewFile);
        }

        $this->sendOutput($this->outputContent);
    }

    /**
     * Displays an error with the given code and message.
     *
     * @param int $code Error code.
     * @param string $message Error message.
     * @param array|null $options Additional options for error details.
     */
    public function show(int $code = 500, string $message = '', ?array $options = null): void
    {
        logMsg('INFO', __METHOD__);
        logMsg('INFO', '', ['code' => $code, 'message' => $message, 'options' => $options]);

        $this->data->merge([
            'code' => $code,
            'message' => $message,
            'options' => $options,
        ]);

        $this->sendOutput($this->renderViewBasedOnCode($code));
    }

    /**
     * Sends the appropriate HTTP response code based on the error or HTTP code.
     */
    public function sendResponseCode(): void
    {
        logMsg('INFO', __METHOD__);

        $code = 500;

        if (isset($this->httpCode) && $this->httpCode > 0) {
            $code = (int)$this->httpCode;
        } elseif (isset($this->code) && $this->code > 0) {
            $code = (int)$this->code;
        }

        if ($this->requestType != 'cli') {
            $this->output->responseCode($code);
        }
    }

    /**
     * Sends the appropriate MIME type for the response.
     */
    public function sendMimeType(): void
    {
        logMsg('INFO', __METHOD__);

        $type = ($this->requestType == 'ajax') ? 'json' : 'html';

        $this->output->contentType($type);
    }

    /**
     * Sends the output content to the client and terminates the script.
     *
     * @param string $content Content to send as the response.
     * @param int $exitCode Exit code for script termination.
     */
    public function sendOutput(string $content, int $exitCode = 1): void
    {
        logMsg('INFO', __METHOD__ . ' ' . $exitCode);
        logMsg('DEBUG', $content);

        $this->output->flush();

        $this->output->write($content);

        $this->sendResponseCode();
        $this->sendMimeType();

        $this->output->send($exitCode);

        // fail safe exit "with error"
        exit($exitCode);
    }

    /**
     * Renders a view based on the error code and optional HTTP code.
     *
     * @param int $code Error code.
     * @param int $httpCode Optional HTTP status code.
     * @return string Rendered view content.
     */
    protected function renderViewBasedOnCode(int $code, int $httpCode = 0): string
    {
        logMsg('INFO', __METHOD__ . ' ' . $code . ' ' . $httpCode);

        // use the code as the view we are looking for
        $view = ($httpCode != 0) ? (string)$httpCode : (string)$code;

        $viewFile = $this->findView($view);

        return !empty($viewFile) ? $this->view->render($viewFile) : $this->viewRaw();
    }

    /**
     * Finds a suitable view file for the error.
     *
     * @param string $view Name of the view file.
     * @return string Path to the view file or an empty string if not found.
     */
    protected function findView(string $view): string
    {
        logMsg('INFO', __METHOD__ . ' ' . $view);

        $foundViewPath = '';

        // let's make sure our local views directory is added to the search as a last alternative
        $this->view->search->addDirectory(__DIR__ . DIRECTORY_SEPARATOR . 'views', DirectorySearch::LAST);

        // did someone already attach output?
        $searchPaths = [
            // search env directory /errors/dev/html/404.php
            implode(DIRECTORY_SEPARATOR, [$this->errorViewDirectory, $this->envDirectory, $this->requestTypeDirectory, $view]),
            // search env directory /errors/html/dev/404.php
            implode(DIRECTORY_SEPARATOR, [$this->errorViewDirectory, $this->requestTypeDirectory, $this->envDirectory, $view]),
            // then search non env directory /errors/html/404.php
            implode(DIRECTORY_SEPARATOR, [$this->errorViewDirectory, $this->requestTypeDirectory, $view]),
            // then just error code directory /errors/404.php
            implode(DIRECTORY_SEPARATOR, [$this->errorViewDirectory, $view]),
            // then just error code directory /errors.php
            implode(DIRECTORY_SEPARATOR, [$this->errorViewDirectory]),
            // then just error code directory /404.php
            implode(DIRECTORY_SEPARATOR, [$view]),
        ];

        foreach ($searchPaths as $searchPath) {
            if ($this->view->search->exists($searchPath)) {
                $foundViewPath = $searchPath;
                break;
            }
        }

        logMsg('INFO', __METHOD__ . ' ' . $foundViewPath);

        return $foundViewPath;
    }

    /**
     * Retrieves a service instance by its name.
     *
     * @param string $name Service name.
     * @param array $arguments Arguments to pass to the service constructor, if necessary.
     * @return mixed The service instance.
     */
    protected function getService(string $name, array $arguments): mixed
    {
        logMsg('INFO', __METHOD__ . ' ' . $name);
        logMsg('DEBUG', '', ['name' => $name, 'arguments' => $arguments]);

        $service = null;

        $name = strtolower($name);

        try {
            $service = container()->get($name);
        } catch (Throwable $e) {
            // fall back to orange classes / services
            $className = ucfirst($name);

            require_once __DIR__ . DIRECTORY_SEPARATOR . $className . '.php';

            $namespace = '\\orange\\framework\\' . $className;

            if (empty($arguments)) {
                $service = $namespace::getInstance();
            } else {
                $service = $namespace::getInstance(...$arguments);
            }
        }

        return $service;
    }

    /**
     * Provides a fallback raw view if no suitable template is found.
     *
     * @return string Raw view content.
     */
    protected function viewRaw(): string
    {
        logMsg('INFO', __METHOD__);

        $output = '';

        // cast to array
        $data = (array)$this->data;

        // fall back to hard coded response format
        switch ($this->requestType) {
            case 'json':
                $output = json_encode($data, JSON_PRETTY_PRINT);
                break;
            case 'html':
                $output .= '<pre>';

                if (isset($data['code'])) {
                    $output .= $data['code'] . PHP_EOL;
                }

                if (isset($data['message'])) {
                    $output .= $data['message'] . PHP_EOL;
                }

                if (isset($data['file'])) {
                    $output .= 'File: ' . $data['file'] . PHP_EOL;
                }

                if (isset($data['line'])) {
                    $output .= 'Line: ' . $data['line'] . PHP_EOL;
                }

                if (isset($data['options'])) {
                    $output .= print_r($data['options'], true) . PHP_EOL;
                }

                $output .= '</pre>';

                break;
            case 'cli':
            default:
                $output = print_r($data, true) . PHP_EOL;
                break;
        }

        return $output;
    }
}
