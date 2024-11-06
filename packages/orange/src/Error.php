<?php

declare(strict_types=1);

namespace orange\framework;

use Throwable;
use orange\framework\ConfigurationTraits;
use orange\framework\interfaces\DataInterface;
use orange\framework\interfaces\ViewInterface;
use orange\framework\interfaces\InputInterface;
use orange\framework\interfaces\OutputInterface;

class Error extends Singleton
{
    use ConfigurationTraits;

    // allow access to all of these when passing it into other classes
    public DataInterface $data;
    public InputInterface $input;
    public ViewInterface $view;
    public OutputInterface $output;

    public int $code = 500;
    public int $httpCode = 0;
    public string $requestType;

    public string $errorViewFolder;
    public string $envFolder;
    public string $requestTypefolder;

    // view file that will be used for output
    public string $viewFile = '';
    public string $outputContent = '';

    protected function __construct(array $config = [], ?Throwable $thrown = null)
    {
        // merge defaults with passed in config
        $this->config = $this->mergeWithDefault($config, 'error');

        // try to setup our services
        // these are loaded from the service container or
        // if it's not loaded we manually load the orange ones
        $this->data = $this->getService('data', []);
        $this->input = $this->getService('input', [[]]);
        $this->view = $this->getService('view', [[], $this->data]);
        $this->output = $this->getService('output', [[]]);

        // base view folder to search for error views
        $this->errorViewFolder = $this->config['error view folder'];

        // assume worst case it's production - also make lowercase because we use this as a folder in the path
        $this->envFolder = defined('ENVIRONMENT') ? strtolower(ENVIRONMENT) : 'production';

        // let's try to determine the output type
        // the output class will auto convert this to a mime type for output
        // html, ajax, cli
        $this->requestType = $this->input->requestType(true);

        // Use this as a folder when looking for an error view file
        $this->requestTypefolder = $this->requestType;

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
                $this->httpCode = $thrown->getHttpCode();
            }

            // if the thrown exception has the method getOutput
            // then call it and write it's output in output
            if (method_exists($thrown, 'getOutput')) {
                $this->outputContent = $thrown->getOutput();
            }

            // if the thrown exception has the method decorate
            // allow the exception the chance to "decorate" the error class
            // this is a catch all incase getHttpCode & getOutput aren't enough
            if (method_exists($thrown, 'decorate')) {
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

    public function show(int $code = 500, string $message = '', ?array $options = null): void
    {
        $this->data->merge([
            'code' => $code,
            'message' => $message,
            'options' => $options,
        ]);

        $this->sendOutput($this->renderViewBasedOnCode($code));
    }

    public function sendResponseCode(): void
    {
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

    public function sendMimeType(): void
    {
        $type = ($this->requestType == 'ajax') ? 'json' : 'html';

        $this->output->contentType($type);
    }

    public function sendOutput(string $content, int $exitCode = 1): void
    {
        $this->output->flush();

        $this->output->write($content);

        $this->sendResponseCode();
        $this->sendMimeType();

        $this->output->send($exitCode);

        // fail safe exit "with error"
        exit($exitCode);
    }

    /* internal methods */
    protected function renderViewBasedOnCode(int $code, int $httpCode = 0): string
    {
        // use the code as the view we are looking for
        $view = ($httpCode != 0) ? (string)$httpCode : (string)$code;

        $viewFile = $this->findView($view);

        return !empty($viewFile) ? $this->view->render($viewFile) : $this->viewRaw();
    }

    protected function findView(string $view): string
    {
        $foundViewPath = '';

        // let's make sure our local views directory is added to the search as a last alternative
        $this->view->search->addDirectory(__DIR__ . DIRECTORY_SEPARATOR . 'views', DirectorySearch::LAST);

        // did someone already attach output?
        $searchPaths = [
            // search env folder /errors/dev/html/404.php
            implode(DIRECTORY_SEPARATOR, [$this->errorViewFolder, $this->envFolder, $this->requestTypefolder, $view]),
            // search env folder /errors/html/dev/404.php
            implode(DIRECTORY_SEPARATOR, [$this->errorViewFolder, $this->requestTypefolder, $this->envFolder, $view]),
            // then search non env folder /errors/html/404.php
            implode(DIRECTORY_SEPARATOR, [$this->errorViewFolder, $this->requestTypefolder, $view]),
            // then just error code folder /errors/404.php
            implode(DIRECTORY_SEPARATOR, [$this->errorViewFolder, $view]),
            // then just error code folder /errors.php
            implode(DIRECTORY_SEPARATOR, [$this->errorViewFolder]),
            // then just error code folder /404.php
            implode(DIRECTORY_SEPARATOR, [$view]),
        ];

        foreach ($searchPaths as $searchPath) {
            if ($this->view->search->exists($searchPath)) {
                $foundViewPath = $searchPath;
                break;
            }
        }

        return $foundViewPath;
    }

    protected function getService(string $name, array $arguments): mixed
    {
        $service = null;

        $name = strtolower($name);

        if (container()->has($name)) {
            $service = container()->get($name);
        } else {
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

    // if we can't find a single template then use this
    protected function viewRaw(): string
    {
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
