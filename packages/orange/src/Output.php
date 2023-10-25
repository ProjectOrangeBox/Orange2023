<?php

declare(strict_types=1);

namespace dmyers\orange;

use dmyers\orange\interfaces\OutputInterface;
use dmyers\orange\exceptions\Output as OutputException;

class Output implements OutputInterface
{
    private static OutputInterface $instance;

    protected array $config = [];
    protected array $mimes = [];
    protected string $output = '';

    // default to http status code ok
    protected int $statusCode = 200;

    protected array $statusCodesInt = [];
    protected array $statusCodes = [];
    protected array $statusCodesNormalized = [];
    protected bool $statusCodeSent = false;

    protected string $contentType = '';
    protected string $charSet = '';
    protected array $cookies = [];
    protected array $headers = [];

    public function __construct(array $config)
    {
        $this->config = mergeDefaultConfig($config, __DIR__ . '/config/output.php');

        // handle mimes merging
        $mimes = require __DIR__ . '/config/mimes.php';

        $this->config['mimes'] = (isset($this->config['mimes'])) ? array_replace_recursive($this->config['mimes'], $mimes) : $mimes;

        $this->mimes = $this->config['mimes'];

        // handle http status code merging
        $statusCodesInt = require __DIR__ . '/config/statusCodes.php';

        $this->config['status codes'] = (isset($this->config['status codes'])) ? array_replace_recursive($this->config['status codes'], $statusCodesInt) : $statusCodesInt;

        $this->statusCodesInt = $this->config['status codes'];

        $this->statusCodes = array_flip($this->statusCodesInt);
        $this->statusCodesNormalized = array_change_key_case($this->statusCodes, CASE_LOWER);

        $this->contentType = $this->config['contentType'];
        $this->charSet = $this->config['charSet'];
    }

    public static function getInstance(array $config): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public function flushAll(): self
    {
        return $this->flush()->flushCookies()->flushHeaders();
    }

    public function send(bool $exit = false): void
    {
        // http_response_code - called
        // header - called
        $this->sendResponseCode()->sendHeaders()->sendCookies();

        // this should be the only echo
        echo $this->output;

        if ($exit) {
            exit(0);
        }
    }

    public function redirect(string $url, int $responseCode = 302, bool $exit = true): void
    {
        $this->flushAll()->header('Location', $url)->responseCode($responseCode)->send($exit);
    }

    public function flush(): self
    {
        $this->output = '';

        return $this;
    }

    public function write(string $string, bool $append = true): self
    {
        if ($append) {
            $this->output .= $string;
        } else {
            $this->output = $string;
        }

        return $this;
    }

    public function get(): string
    {
        return $this->output;
    }

    public function contentType(string $contentType): self
    {
        // if they send in the shorthand content type convert it to a proper content type
        if (isset($this->mimes[$contentType])) {
            $contentType = $this->mimes[$contentType];
        }

        $this->contentType = $contentType;

        return $this;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function header(string $key, string $value): self
    {
        $this->headers[$key] = ['key' => $key, 'value' => $value, 'sent' => false];

        return $this;
    }

    public function getHeaders(): array
    {
        // just the headers
        $headers = [];

        foreach ($this->headers as $header) {
            $headers[] = $header['key'] . ': ' . $header['value'];
        }

        return $headers;
    }

    public function flushHeaders(): self
    {
        foreach ($this->headers as $header) {
            if ($header['sent'] == true) {
                throw new OutputException('Some headers already sent.');
            }
        }

        $this->headers = [];

        return $this;
    }

    public function sendHeaders(): self
    {
        if (headers_sent()) {
            throw new OutputException('Output already started.');
        }

        // add our content type
        $this->header('Content-Type', $this->contentType . '; charset=' . $this->charSet);

        // send headers
        foreach ($this->headers as $index => $header) {
            if (!$header['sent']) {
                header($header['key'] . ': ' . $header['value']);

                // flip send flag
                $this->headers[$index]['sent'] = true;
            }
        }

        return $this;
    }

    public function charSet(string $charSet): self
    {
        $this->charSet = $charSet;

        return $this;
    }

    public function getCharSet(): string
    {
        return $this->charSet;
    }

    public function responseCode(int|string $code): self
    {
        if ($this->statusCodeSent) {
            throw new OutputException('Status response code sent.');
        }

        if (is_string($code)) {
            $code = strtolower($code);

            if (!isset($this->statusCodesNormalized[$code])) {
                throw new OutputException('Unknown HTTP Status Code ' . $code);
            }

            $code = $this->statusCodesNormalized[$code];
        }

        // test the integer
        if (!isset($this->statusCodesInt[$code])) {
            throw new OutputException('Unknown HTTP Status Code ' . (string)$code);
        }

        // code is valid integer
        $this->statusCode = $code;

        return $this;
    }

    public function getResponseCode(): int
    {
        return $this->statusCode;
    }

    public function sendResponseCode(): self
    {
        // actual a header
        http_response_code($this->statusCode);

        $this->statusCodeSent = true;

        return $this;
    }

    public function cookie(string|array $name, string $value = '', int $expire = 0, string $domain = '', string $path = '/', bool $secure = null, bool $httponly = null, string $samesite = null): self
    {
        if (is_array($name)) {
            // always leave 'name' in last place, as the loop will break otherwise, due to $$item
            foreach (['value', 'expire', 'domain', 'path', 'prefix', 'secure', 'httponly', 'samesite', 'name'] as $item) {
                if (isset($name[$item])) {
                    $$item = $name[$item];
                }
            }
        }

        $configCookie = $this->config['cookie'];

        if ($domain == '' && $configCookie['domain'] != '') {
            $domain = $configCookie['domain'];
        }

        if ($path === '/' && $configCookie['path'] !== '/') {
            $path = $configCookie['path'];
        }

        $secure = ($secure === null && $configCookie['secure'] !== null) ? (bool) $configCookie['secure'] : (bool) $secure;
        $httponly = ($httponly === null && $configCookie['httponly'] !== null) ? (bool) $configCookie['httponly'] : (bool) $httponly;

        $expire = ($expire > 0) ? time() + $expire : 0;

        isset($samesite) || $samesite = $configCookie['samesite'];

        if (isset($samesite)) {
            $samesite = ucfirst(strtolower($samesite));
            in_array($samesite, ['Lax', 'Strict', 'None'], true) || $samesite = 'Lax';
        } else {
            $samesite = 'Lax';
        }

        $setCookieOptions = [
            'expires' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite,
        ];

        $this->cookies[$name] = ['name' => $name, 'value' => $value, 'options' => $setCookieOptions, 'sent' => false];

        return $this;
    }

    public function flushCookies(): self
    {
        foreach ($this->cookies as $cookie) {
            if ($cookie['sent'] == true) {
                throw new OutputException('Some cookies already sent.');
            }
        }

        $this->cookies = [];

        return $this;
    }

    public function sendCookies(): self
    {
        // send cookies
        foreach ($this->cookies as $key => $cookie) {
            if (!$cookie['sent']) {
                setcookie($cookie['name'], $cookie['value'], $cookie['setCookieOptions']);

                $this->cookies[$key]['sent'] = true;
            }
        }

        return $this;
    }
}
