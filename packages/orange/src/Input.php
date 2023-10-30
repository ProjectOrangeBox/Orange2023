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
        $path = parse_url($this->server('request_uri', ''), PHP_URL_PATH);

        return ($path !== false) ? $path : '';
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

    public function isHttpsRequest(): bool
    {
        return $this->isHttps;
    }

    public function raw(): mixed
    {
        return $this->input['raw'];
    }

    // try to convert raw to JSON object
    public function rawObj(): mixed
    {
        return json_encode($this->input['raw']);
    }

    public function rawp(string $name = null, $default = null): mixed
    {
        return $this->extract('rawp', $name, $default);
    }

    public function request(string $name = null, $default = null): mixed
    {
        $output = [];

        foreach ($this->config['requestOrder'] as $method) {
            if (!empty($this->input[$method])) {
                $output = $this->extract($method, $name, $default);
            }
        }

        return $output;
    }

    public function post(string $name = null, $default = null): mixed
    {
        return $this->extract('post', $name, $default);
    }

    public function get(string $name = null, $default = null): mixed
    {
        return $this->extract('get', $name, $default);
    }

    public function server(string $name = null, $default = null): mixed
    {
        return $this->extract('server', $name, $default);
    }

    public function file(string $name = null, $default = null): mixed
    {
        return $this->extract('file', $name, $default);
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
            $this->input[$key] = [];

            if (isset($input[$key])) {
                if ($key == 'raw') {
                    $this->input[$key] = $input[$key];

                    parse_str($input[$key], $this->input['rawp']);
                } else {
                    if (!is_array($input[$key])) {
                        throw new InvalidValue('Input key "' . $key . '" does not contain an array.');
                    }

                    $this->input[$key] = $this->cleanKeys($input[$key]);
                }
            }
        }

        // setup the request type based on a few things
        $isAjax = (strtolower($this->server('http_x_requested_with', '')) == 'xmlhttprequest');
        $isJson = (strpos(strtolower($this->server('http_accept', '')), 'application/json') !== false);

        // 2 different checks
        $isCli1 = (!empty($input['PHP_SAPI']) && $input['PHP_SAPI'] === 'CLI');
        $isCli2 = (!empty($input['STDIN']) && $input['STDIN'] === true);

        if ($isAjax || $isJson) {
            $this->requestType = 'ajax';
            $this->requestMethod = $this->server('request_method', '');
        } elseif ($isCli1 || $isCli2) {
            $this->requestType = 'cli';
            $this->requestMethod = 'cli';
        } else {
            $this->requestType = 'html';
            $this->requestMethod = $this->server('request_method', '');
        }

        // is this https
        if ($this->server('https', 'off') !== 'off') {
            $this->isHttps = true;
        } elseif ($this->server('http_x_forwarded_proto', '') === 'https') {
            $this->isHttps = true;
        } elseif ($this->server('http_front_end_https', 'off') !== 'off') {
            $this->isHttps = true;
        } else {
            $this->isHttps = false;
        }

        return $this;
    }

    /* protected */

    protected function extract(string $type, ?string $name = null, $default = null)
    {
        if ($name === null) {
            $value = $this->input[$type];
        } elseif (isset($this->input[$type][$this->cleanKey($name)])) {
            $value = $this->input[$type][$this->cleanKey($name)];
        } else {
            $value = $default;
        }

        return $value;
    }

    protected function cleanKeys(array $inputArray): array
    {
        $outputArray = [];

        foreach ($inputArray as $arrayKey => $arrayValue) {
            $outputArray[$this->cleanKey($arrayKey)] = $arrayValue;
        }

        return $outputArray;
    }

    protected function cleanKey(string $key): string
    {
        $case = ($this->config['convert keys to']) ?? 'lowercase';

        switch (strtolower($case)) {
            case 'lowercase':
                $key = strtolower($key);
                break;
            case 'uppercase':
                $key = strtoupper($key);
                break;
        }

        // do we have a filter for the input keys?
        // @[^a-z0-9 \[\]\-_]+@
        // a-z A-Z 0-9 [ ] - _ (space)
        $re = $this->config['re key filter'] ?? '';

        return empty($re) ? $key :  preg_replace($re, '', $key);
    }
}
