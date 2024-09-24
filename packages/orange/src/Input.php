<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\exceptions\InvalidValue;
use orange\framework\interfaces\InputInterface;

class Input extends Singleton implements InputInterface
{
    protected array $config;

    protected array $input;
    protected array $internal;

    protected string $requestType;
    protected string $requestMethod;
    protected bool $isHttps;
    protected array $remapKeys;

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    protected function __construct(array $config)
    {
        $this->config = Application::mergeDefaultConfig($config, __DIR__ . '/config/input.php', false);

        $this->remapKeys = $this->config['remap keys'] ? array_change_key_case($this->config['remap keys'], CASE_LOWER) : [];

        $this->input = [];
        $this->internal = [];

        // server required
        $this->build(true);

        // if this option is on immediately force https
        if ($this->config['force https']) {
            $this->forceHttps();
        }
    }

    public function replace(array $replace): self
    {
        // find keys you aren't allowed to replace
        $result = array_diff(array_keys($replace), $this->config['replaceable input keys']);

        if (!empty($result)) {
            // if there are any keys they are trying to replace which they aren't allowed
            throw new InvalidValue('You can not replace "' . implode(', ', $result) . '".');
        }

        // ok merge them in only on key not recursively
        $this->config = array_replace($this->config, $replace);

        // server NOT required
        return $this->build(false);
    }

    public function copy(): array
    {
        $input = $this->input;

        // put the raw body back into body as a string
        $input['body'] = $this->rawBody();
        $input['server'] = $this->internal['server'];

        return $input;
    }

    public function requestUri(): string
    {
        return parse_url($this->extract('server', 'request_uri', ''), PHP_URL_PATH);
    }

    public function uriSegment(int $segmentNumber): string
    {
        $segs = explode('/', ltrim($this->requestUri(), '/'));

        return $segs[$segmentNumber - 1] ?? '';
    }

    public function getUrl(int $component = -1): int|string|array|null|false
    {
        return parse_url($this->extract('server', 'request_uri', ''), $component);
    }

    public function requestMethod(bool $asLowercase = true): string
    {
        return ($asLowercase) ? strtolower($this->requestMethod) : strtoupper($this->requestMethod);
    }

    public function requestType(bool $asLowercase = true): string
    {
        return ($asLowercase) ? strtolower($this->requestType) : strtoupper($this->requestType);
    }

    public function isAjaxRequest(): bool
    {
        return $this->requestType == 'ajax';
    }

    public function isCliRequest(): bool
    {
        return $this->requestType == 'cli';
    }

    public function isHttpsRequest(bool $asString = false): bool|string
    {
        $return = $this->isHttps;

        if ($asString) {
            $return = ($this->isHttps) ? 'https' : 'http';
        }

        return $return;
    }

    public function forceHttps(): void
    {
        if (!$this->isHttps) {
            header("Location: https://" . $this->extract('server', 'http_host') . $this->extract('server', 'request_uri'));
            exit();
        }
    }

    public function rawGet(): string
    {
        return $this->internal['get'];
    }

    public function rawBody(): string
    {
        return $this->internal['body'];
    }

    /**
     * This will handle any injected array sets
     *
     * GET, POST, REQUEST, SERVER, COOKIE, FILES, BODY
     *
     * It of course needs to be set in 'valid input keys'
     * in order for replace to attach it to $input
     *
     * $value = $input->request('keyname', true);
     */
    public function __call(string $name, array $arguments): mixed
    {
        $key = $arguments[0] ?? null;
        $default = $arguments[1] ?? null;

        return $this->extract($name, $key, $default);
    }

    /**
     * This will handle any injected array sets
     *
     * GET, POST, REQUEST, SERVER, COOKIE, FILES, BODY
     *
     * $everythingInRequest = $input->request;
     */
    public function __get(string $name): mixed
    {
        return $this->extract($name, null, null);
    }

    /**
     * if you need to "test" if a key is valid
     */
    public function has(string $name, string $key = null): bool
    {
        $undefined = chr(0);

        try {
            $found = $this->extract($name, $key, $undefined) !== $undefined;
        } catch (InvalidValue $e) {
            $found = false;
        }

        return $found;
    }

    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    /**
     * Protected Internal Methods
     */
    protected function build(bool $serverRequired)
    {
        // server is require
        if ($serverRequired && !isset($this->config['server']) || !is_array($this->config['server'])) {
            throw new InvalidValue('server is a required configuration value for input.');
        }

        // load the "input" keys
        // usually post, get, files, cookie, request, server
        $this->setInput($this->config);

        // Set up the internally used values
        // server normalized, get raw, body raw
        $this->setInternal($this->config);

        // determine based on input
        $this->requestType = $this->getRequestType();
        $this->requestMethod = $this->getMethod();
        $this->isHttps = $this->getHttps();

        return $this;
    }

    protected function setInput(array $config): void
    {
        // normalized
        if (isset($config['server'])) {
            foreach ($config['server'] as $key => $value) {
                $this->input['server'][$this->normalizeKey($key)] = $value;
            }
        }

        // load the input variables
        foreach ($this->config['valid input keys'] as $key) {
            if ($key != 'server') {
                $this->input[$key] = is_array($config[$key]) ? $config[$key] : [];
            }
        }

        // auto detected body
        $this->input['body'] = $this->detectBody($config['body']);
    }

    protected function setInternal(array $config): void
    {
        // most raw form of server parameters
        if (isset($config['server'])) {
            $this->internal['server'] = $config['server'];
        }

        // most raw form of get parameters
        $this->internal['get'] = $this->extract('server', 'query-string', '');

        // whatever they sent in for body
        $this->internal['body'] = $config['body'] ?? '';
    }

    /*
     * extract post, get, request or really any other array passed in
     * as long at it's in 'valid input keys'
     */
    protected function extract(string $type, ?string $key = null, mixed $default = null): mixed
    {
        // normalize
        $type = strtolower($type);

        // rename one key to another key
        $type = isset($this->remapKeys[$type]) ? strtolower($this->remapKeys[$type]) : $type;

        if (!isset($this->input[$type])) {
            throw new InvalidValue($type);
        }

        $value = $default;

        if ($type == 'server') {
            if ($key == null) {
                $value = $this->internal['server'];
            } else {
                // normalize the server key
                $key = $this->normalizeKey($key);
                $key2 = $this->normalizeKey('http-' . $key);

                if (isset($this->input['server'][$key])) {
                    $value = $this->input['server'][$key];
                } elseif (isset($this->input['server'][$key2])) {
                    $value = $this->input['server'][$key2];
                }
            }
        } else {
            if ($key === null) {
                $value = $this->input[$type];
            } elseif (isset($this->input[$type][$key])) {
                $value = $this->input[$type][$key];
            }
        }

        return $value;
    }

    protected function getRequestType(): string
    {
        $requestType = 'html';

        // cli detection
        $phpSapi = strtolower($this->config['php_sapi']) ?? ''; // string
        $stdin = $this->config['stdin'] ?? false; // boolean

        if (($this->extract('server', 'http_x_requested_with') == 'xmlhttprequest') || (strpos($this->extract('server', 'http_accept', ''), 'application/json') !== false)) {
            $requestType = 'ajax';
        } elseif ($phpSapi === 'cli' || $stdin === true) {
            $requestType = 'cli';
        }

        return $requestType;
    }

    protected function getMethod(): string
    {
        if ($this->extract('server', 'http_x_http_method_override', '') !== '') {
            $method = $this->extract('server', 'http_x_http_method_override', '');
        } elseif ($this->extract('get', '_method', '') !== '') {
            $method = $this->extract('get', '_method');
        } elseif ($this->extract('body', '_method', '') !== '') {
            $method = $this->extract('body', '_method');
        } elseif ($this->extract('server', 'request_method', '') !== '') {
            $method = $this->extract('server', 'request_method', '');
        } else {
            // I guess it's a CLI request?
            $method = 'cli';
        }

        // normalize
        return strtolower($method);
    }

    protected function getHttps(): bool
    {
        $isHttps = false;

        if ($this->extract('server', 'https', '') == 'on' || $this->extract('server', 'http_x_forwarded_proto', '') === 'https' || $this->extract('server', 'http_front_end_https', '') !== '') {
            $isHttps = true;
        }

        return $isHttps;
    }

    protected function detectBody(string $body): array|string
    {
        $detected = $body;

        if ($this->config['auto detect body']) {
            // try to convert to json array if it's JSON
            $jsonObject = json_decode($body, true);

            if ($jsonObject !== null) {
                $detected = $jsonObject;
            } elseif (!empty($body)) {
                $detected = $this->parseStr($body);
            }
        }

        return $detected;
    }

    /**
     * Properly handle standard:
     * ?foo=1&foo=2
     * as well as PHP's
     * ?foo[]=1&foo[]=2
     */
    protected function parseStr(string $string): array
    {
        // result array
        $array = [];

        // split on outer delimiter
        // loop through each pair
        foreach (explode('&', $string) as $keyvalue) {
            // split into name and value
            list($name, $value) = explode('=', $keyvalue, 2);

            $value = urldecode($value);

            // if name already exists
            if (isset($array[$name])) {
                // stick multiple values into an array
                if (is_array($array[$name])) {
                    $array[$name][] = $value;
                } else {
                    $array[$name] = [$array[$name], $value];
                }
            } else {
                // otherwise, simply stick it in a scalar
                $array[$name] = $value;
            }
        }

        // return result array
        return $array;
    }

    protected function normalizeKey(string $key): string
    {
        return preg_replace("/[^a-z0-9]/", '', strtolower($key));
    }
}
