<?php

declare(strict_types=1);

namespace dmyers\orange;

use dmyers\orange\exceptions\Output as OutputException;
use dmyers\orange\interfaces\OutputInterface;
use dmyers\orange\exceptions\Output as ExceptionsOutput;

class Output implements OutputInterface
{
    private static OutputInterface $instance;

    // default to http status code ok
    protected int $code = 200;
    protected string $contentType = '';
    protected string $charSet = '';
    protected array $headers = [];
    protected string $output = '';
    protected array $config = [];
    protected array $sentHeaders = [];
    protected int $sentCode = 0;
    protected bool $simulate = false;
    protected bool $showAlreadySentError = false;
    protected array $cookies = [];
    protected array $sentCookies = [];
    protected array $mimes = [];
    protected array $statusCodesInt = [];
    protected array $statusCodes = [];
    protected array $statusCodesNormalized = [];

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
        $this->simulate = $this->config['simulate'];
        $this->showAlreadySentError = $this->config['show already sent error'];

        $this->header('Content-Type: ' . $this->contentType . '; charset=' . $this->charSet, 'Content-Type');
    }

    public static function getInstance(array $config): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public function flush(): self
    {
        $this->output = '';

        return $this;
    }

    public function set(string $html): self
    {
        $this->output = $html;

        return $this;
    }

    public function append(string $html): self
    {
        $this->output .= $html;

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

        $this->header('Content-Type: ' . $this->contentType . '; charset=' . $this->charSet, 'Content-Type');

        return $this;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function header(string $header, string $key = null): self
    {
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
        $this->testIfHeadersSent('flushed');

        $this->headers = [];

        return $this;
    }

    public function flushAll(): self
    {
        return $this->flush()->flushCookies()->flushHeaders();
    }

    public function sendHeaders(): self
    {
        $this->testIfHeadersSent('sent');

        if (empty($this->sentHeaders)) {
            foreach ($this->getHeaders() as $header) {
                if (!$this->simulate) {
                    header($header);
                }
                $this->sentHeaders[] = $header;
            }
        }

        return $this;
    }

    protected function testIfHeadersSent(string $context): void
    {
        if (!empty($this->sentHeaders) && $this->showAlreadySentError) {
            throw new ExceptionsOutput('Content has already been sent therefore headers cannot be ' . $context . ' at this time.');
        }
    }

    public function charSet(string $charSet): self
    {
        $this->charSet = $charSet;

        $this->header('Content-Type: ' . $this->contentType . '; charset=' . $this->charSet, 'Content-Type');

        return $this;
    }

    public function getCharSet(): string
    {
        return $this->charSet;
    }

    public function responseCode(int|string $code): self
    {
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
            throw new OutputException('Unknown HTTP Status Code '.(string)$code);
        }

        // code is valid integer
        if ($this->showAlreadySentError) {
            throw new ExceptionsOutput('Response Code Already Sent.');
        }

        $this->code = $code;

        return $this;
    }

    public function getResponseCode(): int
    {
        return $this->code;
    }

    public function sendResponseCode(): self
    {
        if ($this->sentCode != 0) {
            throw new ExceptionsOutput('Response Code Already Sent.');
        }

        if (!$this->simulate) {
            http_response_code($this->code);
        }

        $this->sentCode = $this->code;

        return $this;
    }

    public function send(bool $exit = false): void
    {
        // http_response_code - called
        // header - called
        $this->sendResponseCode()->sendHeaders()->sendCookies();

        if (!$this->simulate) {
            // this should be the only echo
            echo $this->get();

            if ($exit) {
                exit(0);
            }
        }
    }

    public function redirect(string $url, int $responseCode = 302, bool $exit = true): void
    {
        $this->flushAll()->header('Location: ' . $url)->responseCode($responseCode)->send($exit);
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

        $this->cookies[$name] = ['name' => $name, 'value' => $value, 'options' => $setCookieOptions];

        return $this;
    }

    public function flushCookies(): self
    {
        $this->cookies = [];

        return $this;
    }

    public function sendCookies(): self
    {
        if (!$this->simulate) {
            foreach ($this->cookies as $record) {
                setcookie($record['name'], $record['value'], $record['setCookieOptions']);

                $this->sentCookies[$record['name']] = ['name' => $record['name'], 'value' => $record['value'], 'options' => $record['setCookieOptions']];
            }
        }

        return $this;
    }

    public function __debugInfo(): array
    {
        return [
            'config' => $this->config,
            'code' => $this->code,
            'contentType' => $this->contentType,
            'charSet' => $this->charSet,
            'headers' => $this->headers,
            'sent headers' => $this->sentHeaders,
            'sent code' => $this->sentCode,
            'simulate' => $this->simulate,
            'show already sent error' => $this->showAlreadySentError,
            'output' => $this->output,
            'sent cookies' => $this->sentCookies,
            'cookies' => $this->cookies,
            'popular content types' => $this->mimes,
        ];
    }
}
