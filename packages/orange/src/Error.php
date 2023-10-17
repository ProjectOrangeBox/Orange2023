<?php

declare(strict_types=1);

namespace dmyers\orange;

use dmyers\orange\exceptions\InvalidValue;
use dmyers\orange\exceptions\MethodNotFound;
use dmyers\orange\interfaces\ErrorInterface;
use dmyers\orange\interfaces\OutputInterface;
use dmyers\orange\interfaces\ViewerInterface;

class Error implements ErrorInterface
{
    private static ErrorInterface $instance;

    protected ViewerInterface $viewer;
    protected OutputInterface $output;

    protected array $config = [];
    protected array $errors = [];
    protected int $responseCode = 200;
    protected string $mimeType = '';
    protected string $charSet = '';
    protected bool $deduplicate = true;
    protected array $deduplicateStorage = [];
    protected string $requestType = '';
    protected string $detectedRequestType = '';
    protected string $folder = '';
    protected string $defaultTemplate = '';

    public function __construct(array $config, ViewerInterface $viewer, OutputInterface $output)
    {
        $this->config = mergeDefaultConfig($config, __DIR__ . '/config/error.php');

        $this->viewer = $viewer;
        $this->output = $output;

        $this->deduplicate = $this->config['deduplicate'];
        $this->defaultTemplate = $this->config['defaultTemplate'];

        // local orange views folder (last)
        $this->viewer->addPath($this->config['add path']);

        $this->requestType($this->config['request type']);
    }

    public static function getInstance(array $config, ViewerInterface $viewer, OutputInterface $output): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config, $viewer, $output);
        }

        return self::$instance;
    }

    public function responseCode(int $responseCode): self
    {
        $this->responseCode = $responseCode;

        return $this;
    }

    public function requestType(string $requestType): self
    {
        $this->detectedRequestType = $requestType;

        $this->reset();

        return $this;
    }

    public function mimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function charSet(string $charSet): self
    {
        $this->charSet = $charSet;

        return $this;
    }

    public function clear(): self
    {
        $this->errors = [];

        return $this;
    }

    public function reset(): self
    {
        $this->clear();

        $this->requestType = strtolower($this->detectedRequestType);

        if (!in_array($this->requestType, \array_keys($this->config['types']))) {
            throw new InvalidValue('Unknown type "' . $this->requestType . '".');
        }

        $requestConfig = $this->config['types'][$this->requestType];

        $this->mimeType($requestConfig['mime type']);
        $this->charset($requestConfig['charset']);
        $this->folder($requestConfig['folder']);

        return $this;
    }

    public function folder(string $folder): self
    {
        $this->folder = trim($folder, '/');

        return $this;
    }

    public function add(): self
    {
        foreach (func_get_args() as $arg) {
            if ($this->deduplicate) {
                $key = md5(json_encode($arg));

                if (!isset($this->deduplicateStorage[$key])) {
                    $this->errors[] = $arg;
                    $this->deduplicateStorage[$key] = true;
                }
            } else {
                $this->errors[] = $arg;
            }
        }

        return $this;
    }

    /* 403 Forbidden default */
    public function onErrorsShow(string $template): void
    {
        if ($this->has()) {
            $this->show($template);
        }
    }

    public function show(string $template = null): void
    {
        $template = $template ?? $this->defaultTemplate;

        $this->output
            ->flushAll()
            ->responseCode($this->responseCode)
            ->charSet($this->charSet)
            ->contentType($this->mimeType)
            ->set($this->viewer->render($this->folder . '/' . trim($template, '/'), ['errors' => $this->errors]))
            ->send(true);
    }

    public function show404(string $msg = null): void
    {
        $this->reset()->responseCode(404)->add($msg)->show();
    }

    public function show500(string $msg = null): void
    {
        $this->reset()->responseCode(500)->add($msg)->show();
    }

    public function has(): bool
    {
        return (count($this->errors) > 0);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function collectErrors(object $object, string $method = 'errors'): self
    {
        if (!method_exists($object, $method)) {
            throw new MethodNotFound('Errors could not collect from "' . get_class($object) . '" because it does not have a "' . $method . '" method.');
        }

        return $this->add($object->$method());
    }
}
