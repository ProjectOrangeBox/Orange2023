<?php

declare(strict_types=1);

namespace dmyers\orange;

use dmyers\orange\exceptions\InvalidValue;
use dmyers\orange\interfaces\InputInterface;

class Input implements InputInterface
{
    private static InputInterface $instance;
    protected array $input = [];
    protected string $requestType = '';
    protected string $requestMethod = '';
    protected bool $isHttps = false;
    protected string $ipAddress = '';
    protected array $config = [];
    protected array $lowercaseServer = [];

    public function __construct(array $config)
    {
        $this->config = mergeDefaultConfig($config, __DIR__ . '/config/input.php', false);

        $this->replace($this->config);
    }

    public static function getInstance(array $config): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new (get_called_class())($config);
        }

        return self::$instance;
    }

    public function requestUri(): string
    {
        $path = $this->getServer('request_uri');

        if ($path !== '') {
            $path = parse_url($path, PHP_URL_PATH);
        }

        return $path;
    }

    public function uriSegement(int $int): string
    {
        $segs = explode('/', ltrim($this->requestUri(), '/'));

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

    public function isHttpsRequest(bool $asString = false): mixed
    {
        $return = $this->isHttps;

        if ($asString) {
            $return = ($this->isHttps) ? 'https' : 'http';
        }

        return $return;
    }

    /**
     * passing true for name will return the raw body
     * if not it will try to detect the type of payload
     * and return a matching key name
     * or complete payload
     */
    public function body($name = null, $default = null): mixed
    {
        $return = $default;

        if ($name === true) {
            $return = $this->input['body'];
        } else {
            $jsonObject = json_decode($this->input['body']);

            if ($jsonObject !== null) {
                if ($name === null) {
                    $return = $jsonObject;
                } elseif (isset($jsonObject->$name)) {
                    $return = $jsonObject->$name;
                }
            } else {
                parse_str($this->input['body'], $jsonArray);

                if (is_array($jsonArray)) {
                    if ($name === null) {
                        $return = $jsonArray;
                    } elseif (isset($jsonArray[$name])) {
                        $return = $jsonArray[$name];
                    }
                }
            }
        }

        return $return;
    }

    public function get(?string $name = null, $default = null): mixed
    {
        return $this->extract('get', $name, $default);
    }

    public function extract(string $type, ?string $name = null, $default = null)
    {
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

    public function server(string $name = null, $default = null): mixed
    {
        return $this->extract('server', $name, $default);
    }

    public function file(string $name = null, $default = null): mixed
    {
        return $this->extract('files', $name, $default);
    }

    public function cookie(string $name = null, $default = null): mixed
    {
        return $this->extract('cookie', $name, $default);
    }

    /**
     * Get a copy of ONLY the input
     */
    public function copy(): array
    {
        return $this->input;
    }

    /**
     * replace the input
     */
    public function replace(array $input): self
    {
        foreach ($this->config['valid input keys'] as $key) {
            $this->input[$key] = (isset($input[$key])) ? $input[$key] : [];
        }

        $this->lowercaseServer = array_change_key_case($this->input['server'], CASE_LOWER);

        // default
        $this->requestType = 'html';
        $this->requestMethod = $this->getMethod();

        if (($this->getServer('http_x_requested_with') == 'xmlhttprequest') || (strpos($this->getServer('http_accept'), 'application/json') !== false)) {
            $this->requestType = 'ajax';
        } elseif ((!empty($input['PHP_SAPI']) && $input['PHP_SAPI'] === 'CLI') || (!empty($input['STDIN']) && $input['STDIN'] === true)) {
            $this->requestType = 'cli';
            $this->requestMethod = 'cli';
        }

        // is this https
        $this->isHttps = $this->getHttp();

        return $this;
    }

    /* protected */
    protected function getMethod(): string
    {
        $method = $this->getServer('request_method');

        if ($this->getServer('http_x_http_method_override') !== '') {
            $method = $this->getServer('http_x_http_method_override');
        } elseif ($this->get('_method') !== null) {
            $method = $this->get('_method');
        } elseif ($this->body('_method') !== null) {
            $method = $this->body('_method');
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

    protected function getServer(string $name): string
    {
        $name = strtolower($name);

        return (isset($this->lowercaseServer[$name])) ? strtolower($this->lowercaseServer[$name]) : '';
    }
}
