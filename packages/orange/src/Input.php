<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\base\Singleton;
use orange\framework\exceptions\InvalidValue;
use orange\framework\interfaces\InputInterface;
use orange\framework\traits\ConfigurationTrait;

class Input extends Singleton implements InputInterface
{
    use ConfigurationTrait;

    protected array $input = [];
    protected array $internal = [];

    protected string $requestType = '';
    protected string $requestMethod = '';
    protected bool $isHttps = false;
    protected array $remapKeys = [];

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    protected function __construct(array $config)
    {
        logMsg('INFO', __METHOD__);

        $this->config = $this->mergeWithDefault($config, false);

        $this->remapKeys = $this->config['remap keys'] ? array_change_key_case($this->config['remap keys'], CASE_LOWER) : [];

        $this->input = [];
        $this->internal = [];

        // server IS required when we build the input array
        $this->build(true);
    }

    public function replace(array $replace): self
    {
        logMsg('INFO', __METHOD__);
        logMsg('DEBUG', '', $replace);

        // find keys you aren't allowed to replace
        $result = array_diff(array_keys($replace), $this->config['replaceable input keys']);

        // if they passed in a key not in the 'replaceable input keys' array this will NOT be empty
        if (!empty($result)) {
            // if there are any keys they are trying to replace which they aren't allowed
            throw new InvalidValue('You can not replace "' . implode(', ', $result) . '".');
        }

        // ok merge them in only on key not recursively
        $this->config = array_replace($this->config, $replace);

        // server NOT required when we build the input array
        return $this->build(false);
    }

    public function copy(): array
    {
        logMsg('INFO', __METHOD__);
        logMsg('DEBUG', '', $this->input);

        $input = $this->input;

        // put the raw body back into body as a string
        $input['body'] = $this->rawBody();
        $input['server'] = $this->internal['server'];

        return $input;
    }

    public function requestUri(): string
    {
        $uri = parse_url($this->extract('server', 'request_uri', ''), self::PATH);

        logMsg('INFO', __METHOD__ . ' ' . $uri);

        return $uri;
    }

    public function uriSegment(int $segmentNumber): string
    {
        logMsg('INFO', __METHOD__ . ' ' . $segmentNumber);

        $segs = explode('/', ltrim($this->requestUri(), '/'));

        return $segs[$segmentNumber - 1] ?? '';
    }

    public function getUrl(int $component = -1): int|string|array|null|false
    {
        logMsg('INFO', __METHOD__ . ' ' . $component);

        return parse_url($this->extract('server', 'request_uri', ''), $component);
    }

    public function requestMethod(bool $asLowercase = true): string
    {
        $method = ($asLowercase) ? strtolower($this->requestMethod) : strtoupper($this->requestMethod);

        logMsg('INFO', __METHOD__ . ' ' . $method);

        return $method;
    }

    public function requestType(bool $asLowercase = true): string
    {
        $type = ($asLowercase) ? strtolower($this->requestType) : strtoupper($this->requestType);

        logMsg('INFO', __METHOD__ . ' ' . $type);

        return $type;
    }

    public function isAjaxRequest(): bool
    {
        return $this->requestType(true) == 'ajax';
    }

    public function isCliRequest(): bool
    {
        return $this->requestType(true) == 'cli';
    }

    public function isHttpsRequest(bool $asString = false): bool|string
    {
        logMsg('INFO', __METHOD__ . ' ' . $asString);

        $return = $this->isHttps;

        if ($asString) {
            $return = ($this->isHttps) ? 'https' : 'http';
        }

        return $return;
    }

    public function rawGet(): string
    {
        logMsg('INFO', __METHOD__);
        logMsg('DEBUG', '', ['get' => $this->internal['get']]);

        return $this->internal['get'];
    }

    public function rawBody(): string
    {
        logMsg('INFO', __METHOD__);
        logMsg('DEBUG', '', ['body' => $this->internal['body']]);

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
        logMsg('INFO', __METHOD__ . '[' . $name . ']');
        logMsg('DEBUG', '', ['name' => $name, 'arguments' => $arguments]);

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
        logMsg('INFO', __METHOD__ . ' ' . $name);

        return $this->extract($name, null, null);
    }

    /**
     * if you need to "test" if a key is valid
     */
    public function has(string $name, string $key = null): bool
    {
        logMsg('INFO', __METHOD__ . ' ' . $name . ' ' . $key);

        // extract throws an InvalidValue exception if a match is not found
        // so as long as it doesn't throw an exception we
        // can assume it has been found
        $found = true;

        try {
            $this->extract($name, $key, chr(0));
        } catch (InvalidValue $e) {
            $found = false;
        }

        return $found;
    }

    public function __isset(string $name): bool
    {
        logMsg('INFO', __METHOD__ . ' ' . $name);

        return $this->has($name);
    }

    /**
     * Protected Internal Methods
     */
    protected function build(bool $serverRequired)
    {
        // server is require
        if ($serverRequired && !isset($this->config['server']) && !is_array($this->config['server'])) {
            throw new InvalidValue('server is a required configuration value to build input.');
        }

        // load the "input" keys
        // usually post, get, files, cookie, request, server
        $this->setInput($this->config);

        // Set up the internally used values
        // server normalized, get raw, body raw
        $this->setInternal($this->config);

        // determine based on input (strings)
        $this->requestType = $this->getRequestType();
        $this->requestMethod = $this->getMethod();

        // boolean
        $this->isHttps = $this->getHttps();

        return $this;
    }

    protected function setInput(array $config): void
    {
        logMsg('INFO', __METHOD__);
        logMsg('DEBUG', '', $config);

        // we need server in order to detect some internal application functions
        // so we store our own internal version
        if (isset($config['server'])) {
            foreach ($config['server'] as $key => $value) {
                $this->input['server'][$this->normalize($key)] = $value;
            }
        }

        // load the standard input variables
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
        logMsg('DEBUG', __METHOD__, $config);

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
        logMsg('DEBUG', __METHOD__, ['type' => $type, 'key' => $key, 'default' => $default]);

        // normalize
        $type = strtolower($type);

        // rename one key to another key
        $type = isset($this->remapKeys[$type]) ? strtolower($this->remapKeys[$type]) : $type;

        // does this key even exist?
        if (!isset($this->input[$type])) {
            throw new InvalidValue($type);
        }

        // set the value to default unless we find something else
        $value = $default;

        // special case for 'server'
        if ($type == 'server') {
            if ($key == null) {
                $value = $this->internal['server'];
            } else {
                // normalize the server key
                $key = $this->normalize($key);
                $httpKey = $this->normalize('http-' . $key);

                if (isset($this->input['server'][$key])) {
                    $value = $this->input['server'][$key];
                } elseif (isset($this->input['server'][$httpKey])) {
                    $value = $this->input['server'][$httpKey];
                }
            }
        } else {
            // standard input key
            if ($key === null) {
                // if they didn't provide a key they want back the entire config file array
                $value = $this->input[$type];
            } elseif (isset($this->input[$type][$key])) {
                // if they did provide a key return the matching key in the config file
                $value = $this->input[$type][$key];
            }
        }

        return $value;
    }

    protected function getRequestType(): string
    {
        // default to html unless we find something else
        $requestType = 'html';

        if (($this->extract('server', 'http_x_requested_with', '') == 'xmlhttprequest') || (strpos($this->extract('server', 'http_accept', ''), 'application/json') !== false)) {
            $requestType = 'ajax';
        } elseif (strtolower($this->config['php_sapi'] ?? '') === 'cli' || ($this->config['stdin'] ?? false) === true) {
            $requestType = 'cli';
        }

        logMsg('INFO', __METHOD__ . ' ' . $requestType);

        return $requestType;
    }

    protected function getMethod(): string
    {
        /**
         * You can override the http method by setting on of the following in your http request
         */
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
        // set the default to not https unless we find something else
        $isHttps = false;

        if ($this->extract('server', 'https', '') == 'on' || $this->extract('server', 'http_x_forwarded_proto', '') === 'https' || $this->extract('server', 'http_front_end_https', '') !== '') {
            $isHttps = true;
        }

        logMsg('INFO', __METHOD__ . ' ' . ($isHttps ? 'true' : 'false'));

        return $isHttps;
    }

    protected function detectBody(string $body): array|string
    {
        logMsg('INFO', __METHOD__);
        logMsg('DEBUG', '', ['body' => $body]);

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

        logMsg('DEBUG', var_export($detected, true));

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
        logMsg('INFO', __METHOD__);
        logMsg('DEBUG', '', ['string' => $string]);

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

        logMsg('DEBUG', __METHOD__, $array);

        // return result array
        return $array;
    }
}
