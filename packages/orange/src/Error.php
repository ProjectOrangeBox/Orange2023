<?php

declare(strict_types=1);

namespace dmyers\orange;

use dmyers\orange\stubs\Log;
use dmyers\orange\exceptions\InvalidValue;
use dmyers\orange\interfaces\LogInterface;
use dmyers\orange\exceptions\MethodNotFound;
use dmyers\orange\interfaces\ErrorInterface;
use dmyers\orange\interfaces\OutputInterface;
use dmyers\orange\interfaces\ViewerInterface;

class Error implements ErrorInterface
{
    private static ErrorInterface $instance;
    protected OutputInterface $output;
    protected LogInterface $log;
    protected ViewerInterface $viewer;

    protected array $config = [];
    protected array $errors = [];
    protected array $duplicates = [];
    protected string $requestType = '';
    protected array $requestConfig = [];
    protected string $key;

    public function __construct(array $config, ViewerInterface $viewer, OutputInterface $output, ?LogInterface $log = null)
    {
        $this->config = $config;
        $this->viewer = $viewer;
        $this->output = $output;
        $this->log = $log ?? new Log([]);

        // clears all variables
        // set the request type
        // set the default key
        $this->reset();

        // add error view paths to viewer after other view paths
        $this->viewer->addPaths($config['view paths']);

        // local orange views folder (last)
        $this->viewer->addPath(__DIR__ . '/views');
    }

    public static function getInstance(array $config, ViewerInterface $viewer, OutputInterface $output, ?LogInterface $log = null): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config, $viewer, $output, $log);
        }

        return self::$instance;
    }
    /**
     * manually change the request type
     */
    public function requestType(string $requestType): self
    {
        $this->requestType = strtolower($requestType);

        if (!in_array($this->requestType, \array_keys($this->config['types']))) {
            throw new InvalidValue('Unknown type "' . $requestType . '".');
        }

        $this->requestConfig = $this->config['types'][$this->requestType];

        return $this;
    }

    public function add(mixed $value, string $key = null): self
    {
        $key = $key ?? $this->key;

        $dupKey = md5((string)$key . json_encode($value));

        if (!isset($this->duplicates[$dupKey])) {
            $this->errors[$key][] = $value;
            $this->duplicates[$dupKey] = true;
        }

        return $this;
    }

    /* collect errors from another object by calling the collect method */
    public function collectErrors(object $object, string $key = null): self
    {
        $key = $key ?? $this->key;

        if (!method_exists($object, 'errors')) {
            throw new MethodNotFound('Errors could not collect from "' . get_class($object) . '" because it does not have a errors method.');
        }

        $errors = $object->errors($key);

        if (is_array($errors)) {
            foreach ($errors as $error) {
                $this->add($error, $key);
            }
        } else {
            $this->add($errors, $key);
        }

        return $this;
    }

    public function clear(string $key = null): self
    {
        $key = $key ?? $this->key;

        unset($this->errors[$key]);

        return $this;
    }

    public function reset(): self
    {
        $this->errors = [];

        $this->requestType($this->config['request type']);

        $this->key = $this->config['default key'];

        return $this;
    }

    public function has(string $key = null): bool
    {
        $key = $key ?? $this->key;

        return !empty($this->errors($key));
    }

    public function errors(string $key = null): mixed
    {
        $errors = $this->errors;

        if ($key) {
            $errors = $this->errors[$key] ?? null;
        }

        return $errors;
    }

    /**
     * Output Functions
     */
    public function send(int|string $view = null, int $code = 0, ?string $key = null, ?string $requestType = null): void
    {
        $this->display($view, ['errors' => $this->errors($key)], $code, ['request type' => $requestType]);
    }

    public function sendOnError(int|string $view = null, int $code = 0, ?string $key = null, ?string $requestType = null): void
    {
        if ($this->has($key)) {
            $this->send($view, $code, $key, $requestType);
        }
    }

    /**
     * General Error
     */
    public function showError(string $message, int $code = 0, string $heading = 'An Error Was Encountered', int|string $view = null, array $override = []): void
    {
        $view = $view ?? $this->config['default error view'];

        $this->display($view, ['heading' => $heading, 'message' => $message], $code, $override);
    }

    /**
     * Heavy Lifter
     */
    public function display(int|string $view, array $data, int $code = 0, array $override = []): void
    {
        if (isset($override['request type']) && $override['request type'] != null) {
            $this->requestType($override['request type']);
        }

        $view = $view ?? $this->config['default error view'];

        $code = $this->determineStatusCode($view, $code);

        $charSet = $override['charset'] ?? $this->requestConfig['charset'];
        $mimeType = $override['mime type'] ?? $this->requestConfig['mime type'];
        $subFolder = $override['subfolder'] ?? $this->requestConfig['subfolder'];

        if (empty($subFolder)) {
            $finalView = (string)$view;
        } else {
            $finalView = trim($subFolder, '/') . '/' . $view;
        }

        if ($this->log) {
            $this->log->error(\json_encode($data));
        }

        // send with exit
        $this->output->flushAll()->responseCode($code)->charSet($charSet)->contentType($mimeType)->set($this->viewer->render($finalView, $data))->send(true);
    }

    /* protected */

    protected function determineStatusCode(int|string $view, int $code): int
    {
        if ($code == 0) {
            if (is_numeric($view)) {
                $code = (int)$view;
            } else {
                $code = $this->config['default status code'];
            }
        }

        return $code;
    }

    public function __debugInfo(): array
    {
        return [
            'config' => $this->config,
            'errors' => $this->errors,
            'duplicates' => $this->duplicates,
            'requestType' => $this->requestType,
            'requestConfig' => $this->requestConfig,
            'viewer' => $this->viewer,
        ];
    }
}
