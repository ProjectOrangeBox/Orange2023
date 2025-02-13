<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\base\Singleton;
use orange\framework\exceptions\InvalidValue;
use orange\framework\interfaces\InputInterface;
use orange\framework\traits\ConfigurationTrait;

/**
 * Class Input
 *
 * Handles input data and manages HTTP requests using a singleton pattern.
 * Use Singleton::getInstance() to obtain an instance.
 *
 * @package orange\framework
 */
class Input extends Singleton implements InputInterface
{
    /** include ConfigurationTrait methods */
    use ConfigurationTrait;

    /**
     * Stores input data.
     */
    protected array $input = [];

    /**
     * Stores internal values such as server and raw body.
     */
    protected array $internal = [];

    /**
     * Type of the request (e.g., AJAX, CLI, etc.).
     */
    protected string $requestType = '';

    /**
     * HTTP method used for the request (e.g., GET, POST).
     */
    protected string $requestMethod = '';

    /**
     * Indicates whether the request is HTTPS.
     */
    protected bool $isHttps = false;

    /**
     * Stores remapped keys for normalization.
     */
    protected array $remapKeys = [];

    /**
     * Protected constructor to enforce the singleton pattern.
     *
     * @param array $config Configuration data.
     */
    protected function __construct(array $config)
    {
        logMsg('INFO', __METHOD__);

        $this->config = $this->mergeWith($config, false);

        $this->remapKeys = $this->config['remap keys'] ? array_change_key_case($this->config['remap keys'], CASE_LOWER) : [];

        $this->input = [];
        $this->internal = [];

        // server IS required when we build the input array
        $this->build(true);
    }

    /**
     * Replaces configuration values with new ones.
     *
     * @param array $replace Replacement data.
     * @return self
     * @throws InvalidValue If invalid keys are provided.
     */
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

    /**
     * Returns a copy of the input data, including raw body and server data.
     *
     * @return array
     */
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

    /**
     * Retrieves the request URI.
     *
     * @return string
     */
    public function requestUri(): string
    {
        $uri = parse_url($this->extract('server', 'request_uri', ''), self::PATH);

        logMsg('INFO', __METHOD__ . ' ' . $uri);

        return $uri;
    }

    /**
     * Retrieves a specific segment of the URI.
     *
     * @param int $segmentNumber Segment number (1-based index).
     * @return string
     */
    public function uriSegment(int $segmentNumber): string
    {
        logMsg('INFO', __METHOD__ . ' ' . $segmentNumber);

        $segs = explode('/', ltrim($this->requestUri(), '/'));

        return $segs[$segmentNumber - 1] ?? '';
    }

    /**
     * Parses the request URL and retrieves specific components.
     *
     * @param int $component URL component to retrieve.
     * @return int|string|array|null|false
     */
    public function getUrl(int $component = -1): int|string|array|null|false
    {
        logMsg('INFO', __METHOD__ . ' ' . $component);

        return parse_url($this->extract('server', 'request_uri', ''), $component);
    }

    /**
     * Retrieves the HTTP request method.
     *
     * @param bool $asLowercase Whether to return the method in lowercase.
     * @return string
     */
    public function requestMethod(bool $asLowercase = true): string
    {
        $method = ($asLowercase) ? strtolower($this->requestMethod) : strtoupper($this->requestMethod);

        logMsg('INFO', __METHOD__ . ' ' . $method);

        return $method;
    }

    /**
     * Retrieves the request type.
     *
     * @param bool $asLowercase Whether to return the type in lowercase.
     * @return string
     */
    public function requestType(bool $asLowercase = true): string
    {
        $type = ($asLowercase) ? strtolower($this->requestType) : strtoupper($this->requestType);

        logMsg('INFO', __METHOD__ . ' ' . $type);

        return $type;
    }

    /**
     * Checks if the request is an AJAX request.
     *
     * @return bool
     */
    public function isAjaxRequest(): bool
    {
        return $this->requestType(true) == 'ajax';
    }

    /**
     * Checks if the request is a CLI request.
     *
     * @return bool
     */
    public function isCliRequest(): bool
    {
        return $this->requestType(true) == 'cli';
    }

    /**
     * Checks if the request is HTTPS.
     *
     * @param bool $asString Return as string instead of bool.
     * @return bool|string
     */
    public function isHttpsRequest(bool $asString = false): bool|string
    {
        logMsg('INFO', __METHOD__ . ' ' . $asString);

        $return = $this->isHttps;

        if ($asString) {
            $return = ($this->isHttps) ? 'https' : 'http';
        }

        return $return;
    }

    /**
     * Retrieves raw GET data.
     *
     * @return string
     */
    public function rawGet(): string
    {
        logMsg('INFO', __METHOD__);
        logMsg('DEBUG', '', ['get' => $this->internal['get']]);

        return $this->internal['get'];
    }

    /**
     * Retrieves raw body data.
     *
     * @return string
     */
    public function rawBody(): string
    {
        logMsg('INFO', __METHOD__);
        logMsg('DEBUG', '', ['body' => $this->internal['body']]);

        return $this->internal['body'];
    }

    /**
     * Handles dynamic method calls.
     *
     * @param string $name Method name.
     * @param array $arguments Method arguments.
     * @return mixed
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
     * Handles dynamic property access.
     *
     * @param string $name Property name.
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        logMsg('INFO', __METHOD__ . ' ' . $name);

        return $this->extract($name, null, null);
    }

    /**
     * Checks if a key exists in the input data.
     *
     * @param string $name Key name.
     * @param string|null $key Optional sub-key.
     * @return bool
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

    /**
     * Magic isset method.
     *
     * @param string $name Key name.
     * @return bool
     */
    public function __isset(string $name): bool
    {
        logMsg('INFO', __METHOD__ . ' ' . $name);

        return $this->has($name);
    }

    /**
     * Builds the input object with configuration values.
     *
     * @param bool $serverRequired Whether server configuration is required.
     * @return self
     * @throws InvalidValue If server configuration is invalid.
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

    /**
     * Sets input data based on configuration.
     *
     * @param array $config Configuration array.
     */
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

    /**
     * Sets internal configuration values.
     *
     * @param array $config Configuration array.
     */
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

    /**
     * Extracts a value from the input array.
     *
     * @param string $type Input type (e.g., GET, POST).
     * @param string|null $key Optional key to extract.
     * @param mixed $default Default value if the key does not exist.
     * @return mixed
     * @throws InvalidValue If the type is invalid.
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

    /**
     * Determines the request type (e.g., HTML, AJAX, CLI).
     *
     * @return string
     */
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

    /**
     * Determines the HTTP request method.
     *
     * @return string
     */
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

    /**
     * Determines if the request is HTTPS.
     *
     * @return bool
     */
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

    /**
     * Detects the body of the request and converts it to an array if JSON.
     *
     * @param string $body Raw body content.
     * @return array|string
     */
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
     * Parses a query string into an associative array.
     *
     * @param string $string Query string to parse.
     * @return array
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
