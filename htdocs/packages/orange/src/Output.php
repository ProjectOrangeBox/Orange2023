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
    protected bool $statusCodeSent = false;

    protected array $statusCodesInt = [];
    protected array $statusCodes = [];
    protected array $statusCodesNormalized = [];

    protected string $contentType = '';
    protected string $charSet = '';
    protected array $cookies = [];
    protected bool $cookiesSent = false;

    protected array $headers = [];
    protected bool $headersSent = false;

    protected array $predefined = [];

    public function __construct(array $config)
    {
        $this->config = mergeDefaultConfig($config, __DIR__ . '/config/output.php');

        // handle mimes merging
        $mimes = require __DIR__ . '/config/mimes.php';

        $this->config['mimes'] = (isset($this->config['mimes'])) ? array_replace_recursive($this->config['mimes'], $mimes) : $mimes;

        $this->mimes = $this->config['mimes'];

        $this->predefined = $this->config['predefined'];

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
        echo $this->get();

        if ($exit) {
            exit(0);
        }
    }

    public function redirect(string $url, int $responseCode = 302, bool $exit = true): void
    {
        $this->flushAll()->header('Location: ' . $url)->responseCode($responseCode)->send($exit);
    }

    public function flush(): self
    {
        $this->output = '';

        return $this;
    }

    public function write(string $html, bool $append = true): self
    {
        if (!$append) {
            $this->output = $html;
        } else {
            $this->output .= $html;
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

    public function header(string $header, string $key = null): self
    {
        $this->alreadySent('Headers', $this->headersSent);

        if ($key === null) {
            $segs = explode(':', $header);
            $key = strtolower(trim($segs[0]));
        }

        $this->headers[$key] = $header;

        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function flushHeaders(): self
    {
        $this->alreadySent('Headers', $this->headersSent);

        $this->headers = [];

        return $this;
    }

    public function sendHeaders(): self
    {
        $this->alreadySent('Headers', $this->headersSent);

        // add our content type
        $this->header('Content-Type: ' . $this->contentType . '; charset=' . $this->charSet, 'Content-Type');

        foreach ($this->getHeaders() as $header) {
            header($header);
        }

        // Send content length
        if ($this->config['send length'] === true) {
            $length = extension_loaded('mbstring') ? mb_strlen($this->output, 'latin1') : strlen($this->output);

            if ($length > 0) {
                header('Content-Length: ' . $length);
            }
        }

        $this->headersSent = true;

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
        $this->alreadySent('Response Code', $this->statusCodeSent);

        if (is_string($code)) {
            $code = strtolower($code);

            if (!isset($this->statusCodesNormalized[$code])) {
                throw new OutputException('Unknown HTTP Status Code ' . $code);
            } else {
                $code = $this->statusCodesNormalized[$code];
            }
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
        $this->alreadySent('Response Code', $this->statusCodeSent);

        http_response_code($this->statusCode);

        $this->statusCodeSent = true;

        return $this;
    }

    public function predefined(string $key): self
    {
        if (!isset($this->predefined[$key])) {
            throw new OutputException('Unknown Predefined Output Key ' . $key);
        }

        $set = array_change_key_case($this->predefined[$key], CASE_LOWER);

        // redirect sends and exits nothing else will setup
        if (isset($set['redirect'])) {
            $this->redirect($set['redirect']);
        }

        if (isset($set['flushall'])) {
            $this->flushAll();
        }

        if (isset($set['contenttype'])) {
            $this->contentType($set['contenttype']);
        }

        if (isset($set['charset'])) {
            $this->charSet($set['charset']);
        }

        if (isset($set['responsecode'])) {
            $this->responseCode($set['responsecode']);
        }

        if (isset($set['header'])) {
            if (is_array($set['header'])) {
                foreach ($set['header'] as $h) {
                    $this->header($h);
                }
            } else {
                $this->header($set['header']);
            }
        }

        if (isset($set['cookie'])) {
            if (is_array($set['cookie'])) {
                foreach ($set['cookie'] as $h) {
                    $this->cookie($h);
                }
            } else {
                $this->cookie($set['cookie']);
            }
        }

        if (isset($set['write'])) {
            $this->write($set['write']);
        }

        if (isset($set['send'])) {
            $this->send();
        }

        return $this;
    }

    public function cookie(string|array $name, string $value = '', int $expire = 0, string $domain = '', string $path = '/', bool $secure = null, bool $httponly = null, string $samesite = null): self
    {
        $this->alreadySent('Cookies', $this->cookiesSent);

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

        $this->cookies[$name] = ['name' => $name, 'value' => $value, 'options' => $setCookieOptions];

        return $this;
    }

    public function flushCookies(): self
    {
        $this->alreadySent('Cookies', $this->cookiesSent);

        $this->cookies = [];

        return $this;
    }

    public function sendCookies(): self
    {
        $this->alreadySent('Cookies', $this->cookiesSent);

        foreach ($this->cookies as $record) {
            setcookie($record['name'], $record['value'], $record['setCookieOptions']);
        }

        $this->cookiesSent = true;

        return $this;
    }

    protected function alreadySent(string $blank, bool $test): void
    {
        if ($test) {
            throw new OutputException($blank . ' already sent.');
        }
    }

    public function __debugInfo(): array
    {
        return [
            'config' => $this->config,
            'code' => $this->statusCode,
            'contentType' => $this->contentType,
            'charSet' => $this->charSet,
            'headers' => $this->headers,
            'headers sent' => $this->headersSent,
            'status code sent' => $this->statusCodeSent,
            'output' => $this->output,
            'cookies sent' => $this->cookiesSent,
            'cookies' => $this->cookies,
            'popular content types' => $this->mimes,
        ];
    }
}
