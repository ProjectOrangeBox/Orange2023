<?php

declare(strict_types=1);

namespace dmyers\orange;

use dmyers\orange\exceptions\InvalidValue;
use dmyers\orange\interfaces\InputInterface;
use dmyers\orange\exceptions\InvalidConfigurationValue;

class Input implements InputInterface
{
    private static InputInterface $instance;

    protected array $config = [];

    protected array $input = [];
    protected array $internal = [];

    protected string $requestType = '';
    protected string $requestMethod = '';
    protected bool $isHttps = false;
    protected bool $tempDefaultSet = false;
    protected $tempDefault;

    protected string $php_sapi;
    protected bool $stdin;

    public function __construct(array $config)
    {
        $this->config = mergeDefaultConfig($config, __DIR__ . '/config/input.php', false);

        $this->initialize(true);
    }

    public static function getInstance(array $config): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new (get_called_class())($config);
        }

        return self::$instance;
    }

    public function replace(array $input): self
    {
        foreach ($input as $key => $value) {
            $key = strtolower($key);

            if (!in_array($key, ['post', 'get', 'files', 'cookie', 'request', 'server', 'body', 'get search keys', 'php_sapi', 'stdin'])) {
                throw new InvalidValue('You can not replace "' . $key . '".');
            }

            $this->config[$key] = $value;
        }

        $this->initialize(false);

        return $this;
    }

    /**
     * replace the input
     */
    protected function initialize(bool $serverRequired): void
    {
        // work on copy
        $input = array_change_key_case($this->config, CASE_LOWER);

        // server is require on construct but not after that
        if (!isset($input['server']) && $serverRequired) {
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

        $this->internal['internal_server'] = array_change_key_case($this->input['server'], CASE_LOWER);

        // most raw form of Get parameters
        $this->internal['get'] = $this->getUrl(PHP_URL_QUERY);

        $this->php_sapi = strtolower($input['php_sapi']) ?? ''; // string
        $this->stdin = $input['stdin'] ?? false; // boolean

        $this->requestType = $this->getRequestType();
        $this->requestMethod = $this->getMethod();
        $this->isHttps = $this->getHttp();
    }

    /**
     * Get a copy of ONLY the input
     */
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
        return ($this->requestType == 'ajax');
    }

    public function isCliRequest(): bool
    {
        return ($this->requestType == 'cli');
    }

    public function isHttpsRequest(bool $asString = false): mixed
    {
        $return = $this->isHttps;

        if ($asString) {
            $return = ($this->isHttps) ? 'https' : 'http';
        }

        return $return;
    }

    /*
     * extract post, get, request or really any other array passed in 
     * as long at it's in 'valid input keys'
     */
    public function extract(string $type, ?string $name = null, mixed $default = null): mixed
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

    /**
     * $value = $input->keyname;
     * $value = $input->withDefault(true)->keyname;
     */
    public function __get(string $name): mixed
    {
        $value = ($this->tempDefaultSet) ? $this->tempDefault : $this->default;
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

    /* protected */
    protected function getRequestType(): string
    {
        $requestType = 'html';

        if (($this->getServer('http_x_requested_with') == 'xmlhttprequest') || (strpos($this->getServer('http_accept'), 'application/json') !== false)) {
            $requestType = 'ajax';
        } elseif ($this->php_sapi === 'cli' || $this->stdin === true) {
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

        if ($this->getServer('https') !== '') {
            $isHttps = true;
        } elseif ($this->getServer('http_x_forwarded_proto') === 'https') {
            $isHttps = true;
        } elseif ($this->getServer('http_front_end_https') !== '') {
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

        return $array;
    }

    /**
     * converts the value to lowercase
     */
    protected function getServer(string $key, mixed $default = ''): string
    {
        $key = strtolower($key);

        return (isset($this->internal['internal_server'][$key])) ? strtolower($this->internal['internal_server'][$key]) : $default;
    }
}
