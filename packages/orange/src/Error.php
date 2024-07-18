<?php

declare(strict_types=1);

namespace orange\framework;

use Throwable;
use orange\framework\interfaces\DataInterface;
use orange\framework\interfaces\ViewInterface;
use orange\framework\interfaces\InputInterface;
use orange\framework\interfaces\OutputInterface;

class Error
{
    protected array $config;

    public DataInterface $data;
    public InputInterface $input;
    public ViewInterface $view;
    public OutputInterface $output;

    public function __construct(array $config = [])
    {
        $this->config = array_replace([
            'base' => '',
            'env' => '',
            'folder' => '',
            'code' => 500,
            'httpCode' => 0,
            'requestType' => null
        ], $config);

        $this->data = $this->getService('data', []);
        $this->input = $this->getService('input', [[]]);
        $this->view = $this->getService('view', [[], $this->data]);
        $this->output = $this->getService('output', [[]]);

        // flush anything already in the output
        $this->output->flush();

        // base view folder to search for error views
        $this->config['base'] = $this->config['error view folder'] ?? 'errors';

        // assume worst case it's production - also make lowercase because we use this as a folder in the path
        $this->config['env'] = defined('ENVIRONMENT') ? strtolower(ENVIRONMENT) : 'production';

        // let's try to determine the output type
        // the output class will auto convert these to mime types
        $this->config['requestType'] = $this->input->requestType(true);

        // what kind of error do we need to return html, ajax, cli?
        $this->config['folder'] = $this->config['requestType'];

        if (isset($config['exception']) && $config['exception'] instanceof Throwable) {
            $thrown = $config['exception'];

            // if an exception is attached then an exception created this class so grab the code and message
            $this->data->merge([
                'message' => $thrown->getMessage(),
                'code' => $thrown->getCode(),
                'options' => $thrown->getTrace(),
                'line' => $thrown->getLine(),
                'file' => $thrown->getFile(),
            ]);

            if ($thrown->getCode() > 0) {
                $this->config['code'] = $thrown->getCode();
            }

            if (method_exists($thrown, 'getHttpCode')) {
                $this->config['httpCode'] = $thrown->getHttpCode();
            }

            // allow the exception the chance to "decorate" the error class
            if (method_exists($thrown, 'decorate')) {
                $thrown->decorate($this);
            }
        }

        // should we just send it?
        if (isset($config['send']) && $config['send']) {
            $this->buildView()->send();
        }
    }

    /**
     * @param int $code
     * @param string $message
     * @param array $options
     * @return void
     */
    public function show(int $code = 500, string $message = '', ?array $options = null): void
    {
        $this->config['code'] = $code;

        $this->data->merge([
            'code' => $code,
            'message' => $message,
            'options' => $options,
        ]);

        $this->buildView()->send();
    }

    public function changeConfig(string $key, string $value): self
    {
        $this->config[$key] = $value;

        return $this;
    }

    public function send(int $exitCode = 1): void
    {
        // send output - exit(1) in calling method
        $this->output->send($exitCode);

        // fail safe exit "with error"
        exit($exitCode);
    }

    /* internal */
    protected function buildView()
    {
        // let's make sure our local views directory is added to the search as a last alternative
        $this->view->search->addDirectory(__DIR__ . '/views', DirectorySearch::LAST);

        $viewFile = (!empty($this->config['httpCode'])) ? (string)$this->config['httpCode'] : (string)$this->config['code'];

        // did someone already attach output?
        if (empty($this->output->get())) {
            $searchPaths = [
                // search env folder /errors/dev/cli/404.php
                implode('/', [$this->config['base'], $this->config['env'], $this->config['folder'], $viewFile]),
                // then search non env folder /errors/cli/404.php
                implode('/', [$this->config['base'], $this->config['folder'], $viewFile]),
                // then just error code folder /errors/404.php
                implode('/', [$this->config['base'], $viewFile]),
                // then just error code folder /errors.php
                implode('/', [$this->config['base']]),
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

            require_once __DIR__ . '/' . $className . '.php';

            $namespace = '\\orange\\framework\\' . $className;

            $service = $namespace::getInstance(...$arguments);
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
        switch ($this->config['requestType']) {
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
