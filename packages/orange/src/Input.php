<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\Singleton;
use orange\framework\exceptions\InvalidValue;
use orange\framework\interfaces\InputInterface;
use orange\framework\exceptions\InvalidConfigurationValue;

class Input extends Singleton implements InputInterface
{
    private static ?InputInterface $instance = null;

    protected array $config = [];

    protected array $input = [];
    protected array $internal = [];

    protected string $requestType = '';
    protected string $requestMethod = '';
    protected bool $isHttps = false;

    protected bool $tempDefaultSet = false;
    protected mixed $tempDefault;

    protected string $phpSapi;
    protected bool $stdin;

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    protected function __construct(array $config)
    {
        $this->config = mergeDefaultConfig($config, __DIR__ . '/config/input.php', false);

        $this->replace([]);
    }

    public static function getInstance(array $config): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public function replace(array $replace): self
    {
        foreach ($replace as $key => $value) {
            $key = strtolower($key);

            if (!in_array($key, ['post', 'get', 'files', 'cookie', 'request', 'server', 'body', 'get search keys', 'php_sapi', 'stdin'])) {
                throw new InvalidValue('You can not replace "' . $key . '".');
            }

            $this->config[$key] = $value;
        }

        // work on copy
        $input = array_change_key_case($this->config, CASE_LOWER);

        // server is require on construct but not after that
        if (!isset($this->config['server'])) {
            throw new InvalidConfigurationValue('server is a required configuration value for input.');
        }

        // default
        $this->internal['body'] = '';

        if (isset($input['body'])) {
            if (!is_string($input['body'])) {
                throw new InvalidConfigurationValue('body is set but it is not a string.');
            }

            $this->internal['body'] = $input['body'];
        }

        // let's try to convert it into an array or json object
        $this->input['body'] = $this->getBody($this->internal['body']);

        // load up all the other default input variable3s
        foreach (['post', 'get', 'files', 'cookie', 'request', 'server'] as $key) {
            $this->input[$key] = $input[$key] ?? [];
        }

        $this->internal['server'] = array_change_key_case($this->input['server'], CASE_LOWER);

        // most raw form of Get parameters
        $this->internal['get'] = $this->getUrl(PHP_URL_QUERY);

        $this->phpSapi = strtolower($input['php_sapi']) ?? ''; // string
        $this->stdin = $input['stdin'] ?? false; // boolean

        $this->requestType = $this->getRequestType();
        $this->requestMethod = $this->getMethod();
        $this->isHttps = $this->getHttp();

        return $this;
    }

    public function copy(): array
    {
        $input = $this->input;

        // put the raw body back into body as a string
        $input['body'] = $this->rawBody();

        return $input;
    }

    public function requestUri(): string
    {
        return $this->getUrl(PHP_URL_PATH);
    }

    public function uriSegement(int $int): string
    {
        $segs = explode('/', ltrim($this->requestUri(), '/'));

        return $segs[$int - 1] ?? '';
    }

    public function getUrl(int $component = -1): int|string|array|null|false
    {
        return parse_url($this->getServer('request_uri', ''), $component);
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
        return $this->requestType == 'ajax';
    }

    public function isCliRequest(): bool
    {
        return $this->requestType == 'cli';
    }

    public function isHttpsRequest(bool $asString = false): mixed
    {
        $return = $this->isHttps;

        if ($asString) {
            $return = ($this->isHttps) ? 'https' : 'http';
        }

        return $return;
    }

    /**
     * This will handle any injected array sets
     *
     * GET, POST, REQUEST, SERVER, COOKIE, FILES, BODY
     *
     * It of course needs to be set in 'valid input keys'
     * in order for replace to attach it to $input
     *
     * $value = $input->get('keyname',true);
     */
    public function __call(string $name, array $arguments): mixed
    {
        $type = strtolower($name);
        $name = $arguments[0] ?? null;
        $default = $arguments[1] ?? null;

        return $this->extract($type, $name, $default);
    }

    public function rawGet(): array
    {
        return $this->internal['get'];
    }

    public function rawBody(): string
    {
        return $this->internal['body'];
    }

    public function __get(string $name): mixed
    {
        $value = ($this->tempDefaultSet) ? $this->tempDefault : null;
        
        $this->tempDefaultSet = false;

        $name = strtolower($name);

        foreach ($this->config['get search keys'] as $key) {
            if (isset($this->input[$key][$name])) {
                $value = $this->input[$key][$name];
                break;
            }
        }

        return $value;
    }

    public function withDefault($tempDefault): self
    {
        $this->tempDefaultSet = true;

        $this->tempDefault = $tempDefault;

        return $this;
    }

    /*
     * extract post, get, request or really any other array passed in
     * as long at it's in 'valid input keys'
     */
    protected function extract(string $type, ?string $name = null, mixed $default = null): mixed
    {
        $type = strtolower($type);

        if (!isset($this->input[$type])) {
            throw new InvalidValue($type);
        }

        $value = $default;

        if ($name === null) {
            $value = $this->input[$type];
        } elseif (isset($this->input[$type][$name])) {
            $value = $this->input[$type][$name];
        }

        return $value;
    }

    protected function getRequestType(): string
    {
        $requestType = 'html';

        if (($this->getServer('http_x_requested_with') == 'xmlhttprequest') || (strpos($this->getServer('http_accept'), 'application/json') !== false)) {
            $requestType = 'ajax';
        } elseif ($this->phpSapi === 'cli' || $this->stdin === true) {
            $requestType = 'cli';
        }

        return $requestType;
    }

    protected function getMethod(): string
    {
        if ($this->getServer('http_x_http_method_override') !== '') {
            $method = $this->getServer('http_x_http_method_override');
        } elseif ($this->extract('get', '_method', '') !== '') {
            $method = $this->extract('get', '_method');
        } elseif ($this->extract('body', '_method', '') !== '') {
            $method = $this->extract('body', '_method');
        } elseif ($this->getServer('request_method') !== '') {
            $method = $this->getServer('request_method');
        } else {
            // I guess it's a CLI request?
            $method = 'cli';
        }

        return strtolower($method);
    }

    protected function getHttp(): bool
    {
        $isHttps = false;

        if ($this->getServer('https') !== '' || $this->getServer('http_x_forwarded_proto') === 'https' || $this->getServer('http_front_end_https') !== '') {
            $isHttps = true;
        }

        return $isHttps;
    }

    protected function getBody(string $body): array
    {
        $array = [];

        // try to convert to json object if it's JSON
        $jsonObject = json_decode($body);

        if ($jsonObject !== null) {
            $array = $jsonObject;
        } else {
            // try to parse it like a string
            // ie default
            parse_str($body, $jsonArray);

            if (is_array($jsonArray)) {
                $array = $jsonArray;
            }
        }

        return (array)$array;
    }

    /**
     * converts the value to lowercase
     */
    protected function getServer(string $key, mixed $default = ''): string
    {
        $key = strtolower($key);

        return (isset($this->internal['server'][$key])) ? strtolower($this->internal['server'][$key]) : $default;
    }
}
