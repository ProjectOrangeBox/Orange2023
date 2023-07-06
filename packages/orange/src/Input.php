<?php

declare(strict_types=1);

namespace dmyers\orange;

use dmyers\orange\interfaces\InputInterface;

class Input implements InputInterface
{
    private static InputInterface $instance;
    protected array $input = [];
    protected string $requestType = '';
    protected string $requestMethod = '';
    protected bool $isHttps = false;
    protected array $validKeys = ['post', 'get', 'request', 'server', 'file', 'raw', 'cookie'];

    public function __construct(array $config)
    {
        $this->replace($config);
    }

    public static function getInstance(array $config): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public function requestUri(): string
    {
        return isset($this->input['server']['request_uri']) ? $this->input['server']['request_uri'] : '';
    }

    public function uriSegement(int $int): string
    {
        $segs = explode('/', ltrim($this->requestUri(),'/'));

        return $segs[$int - 1] ?? '';
    }

    public function requestMethod(bool $lowercase = true): string
    {
        return ($lowercase) ? strtolower($this->requestMethod) : strtoupper($this->requestMethod);
    }

    public function requestType(bool $lowercase = true): string
    {
        return ($lowercase) ? strtolower($this->requestType) : strtoupper($this->requestType);
    }

    public function isAjaxRequest(): bool
    {
        return ($this->requestType == 'ajax');
    }

    public function isCliRequest(): bool
    {
        return ($this->requestType == 'cli');
    }

    public function isHttpsRequest(): bool
    {
        return $this->isHttps;
    }

    public function raw(): mixed
    {
        return $this->pick('raw');
    }

    public function post(string $name = null, $default = null): mixed
    {
        return $this->pick('post', $name, $default);
    }

    public function get(string $name = null, $default = null): mixed
    {
        return $this->pick('get', $name, $default);
    }

    public function request(string $name = null, $default = null): mixed
    {
        return $this->pick('request', $name, $default);
    }

    public function server(string $name = null, $default = null): mixed
    {
        return $this->pick('server', $name, $default);
    }

    public function file(string $name = null, $default = null): mixed
    {
        return $this->pick('file', $name, $default);
    }

    public function cookie(string $name = null, $default = null): mixed
    {
        return $this->pick('cookie', $name, $default);
    }

    public function copy(): array
    {
        return $this->input;
    }

    public function replace(array $input): self
    {
        foreach ($this->validKeys as $key) {
            $this->input[$key] = (isset($input[$key])) ? array_change_key_case((array)$input[$key], CASE_LOWER) : [];
        }

        // setup the request type based on a few things
        $isAjax = (!empty($this->input['server']['http_x_requested_with']) && strtolower($this->input['server']['http_x_requested_with']) == 'xmlhttprequest');
        $isJson = (!empty($this->input['server']['http_accept']) && strpos(strtolower($this->input['server']['http_accept']), 'application/json') !== false);
        $isCli = (strtoupper(PHP_SAPI) === 'CLI' || defined('STDIN'));

        if ($isAjax || $isJson) {
            $this->requestType = 'ajax';
        } elseif ($isCli) {
            $this->requestType = 'cli';
        } else {
            $this->requestType = 'html';
        }

        // get the http request method
        $this->requestMethod = ($isCli) ? 'cli' : $this->input['server']['request_method'];

        // is this https
        if (!empty($this->input['server']['https']) && $this->input['server']['https'] !== 'off') {
            $this->isHttps = true;
        } elseif (isset($this->input['server']['http_x_forwarded_proto']) && $this->input['server']['http_x_forwarded_proto'] === 'https') {
            $this->isHttps = true;
        } elseif (!empty($this->input['server']['http_front_end_https']) && $this->input['server']['http_front_end_https'] !== 'off') {
            $this->isHttps = true;
        } else {
            $this->isHttps = false;
        }

        return $this;
    }

    /* protected */

    protected function pick(string $type, ?string $name = null, $default = null)
    {
        if ($name === null) {
            $value = $this->input[$type];
        } elseif (isset($this->input[$type][strtolower($name)])) {
            $value = $this->input[$type][strtolower($name)];
        } else {
            $value = $default;
        }

        return $value;
    }

    public function __debugInfo(): array
    {
        return [
            'input' => $this->input,
            'requestType' => $this->requestType,
            'requestMethod' => $this->requestMethod,
            'isHttps' => $this->isHttps,
            'validKeys' => $this->validKeys,
        ];
    }
}
