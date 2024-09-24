<?php

declare(strict_types=1);

namespace orange\framework;

use Throwable;
use orange\framework\interfaces\DataInterface;
use orange\framework\interfaces\ViewInterface;
use orange\framework\interfaces\InputInterface;
use orange\framework\interfaces\OutputInterface;

class Error extends Singleton
{
    protected array $config;

    public DataInterface $data;
    public InputInterface $input;
    public ViewInterface $view;
    public OutputInterface $output;
    public Throwable $thrown;

    public int $code = 500;
    public int $httpCode;
    public string $requestType;

    public string $errorViewFolder;
    public string $env;
    public string $requestTypefolder;

    public string $viewfile = '';

    protected function __construct(array $config = [], ?Throwable $thrown = null)
    {
        // merge defaults with passed in config
        $this->config = array_replace_recursive(include __DIR__ . '/config/error.php', $config);

        // try to setup our services
        // these are loaded from the service container or
        // if it's not loaded we manually load the orange ones
        $this->data = $this->getService('data', []);
        $this->input = $this->getService('input', [[]]);
        $this->view = $this->getService('view', [[], $this->data]);
        $this->output = $this->getService('output', [[]]);

        // flush anything already in the output
        $this->output->flush();

        // assume worst case it's production - also make lowercase because we use this as a folder in the path
        $this->env = defined('ENVIRONMENT') ? strtolower(ENVIRONMENT) : 'production';

        // base view folder to search for error views
        $this->errorViewFolder = $this->config['error view folder'];

        // let's try to determine the output type
        // the output class will auto convert these to mime types
        // html, ajax, cli
        $this->requestType = $this->input->requestType(true);

        // what kind of error do we need to return html, ajax, cli?
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

            // if the thrown exception has the method decorate
            // allow the exception the chance to "decorate" the error class
            if (method_exists($thrown, 'decorate')) {
                $thrown->decorate($this);
            }
        }

        // did they set the output directly from there exception using decorate?
        if (empty($this->output->get())) {
            // no - then we better figure out what view to show
            if (empty($this->viewfile)) {
                $this->viewfile = (!empty($this->httpCode)) ? (string)$this->httpCode : (string)$this->code;
            }

            // build the view
            $this->buildOutput($this->viewfile);
        }

        $this->sendOutput();
    }

    public function show(int $code = 500, string $message = '', ?array $options = null): void
    {
        $this->code = $code;

        $this->data->merge([
            'code' => $code,
            'message' => $message,
            'options' => $options,
        ]);

        $this->buildOutput((string)$code)->sendOutput();
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

    public function sendOutput(int $exitCode = 1): void
    {
        $this->sendResponseCode();
        $this->sendMimeType();

        $this->output->send($exitCode);

        // fail safe exit "with error"
        exit($exitCode);
    }

    /* internal methods */

    protected function buildOutput(string $viewFile): self
    {
        // let's make sure our local views directory is added to the search as a last alternative
        $this->view->search->addDirectory(__DIR__ . DIRECTORY_SEPARATOR . 'views', DirectorySearch::LAST);

        // did someone already attach output?
        if (empty($this->output->get())) {
            $searchPaths = [
                // search env folder /errors/dev/cli/404.php
                implode(DIRECTORY_SEPARATOR, [$this->errorViewFolder, $this->env, $this->requestTypefolder, $viewFile]),
                // then search non env folder /errors/cli/404.php
                implode(DIRECTORY_SEPARATOR, [$this->errorViewFolder, $this->requestTypefolder, $viewFile]),
                // then just error code folder /errors/404.php
                implode(DIRECTORY_SEPARATOR, [$this->errorViewFolder, $viewFile]),
                // then just error code folder /errors.php
                implode(DIRECTORY_SEPARATOR, [$this->errorViewFolder]),
            ];

            foreach ($searchPaths as $viewPath) {
                if ($this->view->search->exists($viewPath)) {
                    $this->output->write($this->view->render($viewPath));
                    break;
                }
            }
        }

        // did we find a view using the service?
        if (empty($this->output->get())) {
            // fall back to the most basic error
            $this->output->write($this->viewRaw());
        }

        return $this;
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
