<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\base\Singleton;
use orange\framework\interfaces\InputInterface;
use orange\framework\traits\ConfigurationTrait;
use orange\framework\interfaces\OutputInterface;
use orange\framework\exceptions\output\Output as OutputException;

class Output extends Singleton implements OutputInterface
{
    use ConfigurationTrait;

    protected string $output = '';
    protected array $headers = [];

    protected int $responseCode = 200;
    protected array $responseCodesInternalStringKeys = [];

    protected string $contentType = '';
    protected string $charSet = '';

    protected array $mimes = [];

    protected InputInterface $input;

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    protected function __construct(array $config, InputInterface $input)
    {
        logMsg('INFO', __METHOD__);

        $this->config = $this->mergeWithDefault($config);

        $this->input = $input;

        // if this option is on immediately force https
        if ($this->config['force https']) {
            $this->forceHttps();
        }

        // raw output
        $this->output = '';

        // storage for all headers
        $this->headers = [];

        $this->responseCodesInternalStringKeys = array_change_key_case(array_flip($this->config['status codes']), CASE_LOWER);

        // mimes by file extension
        $this->mimes = $this->config['mimes'] ?? [];

        $this->responseCode($this->responseCode);
        $this->contentType($this->config['contentType']);
        $this->charSet($this->config['charSet']);
    }

    public function redirect(string $url, int $responseCode = 0, bool $exit = true): void
    {
        logMsg('INFO', __METHOD__ . ' ' . $url . ' ' . $responseCode . ' ' . $exit);

        $responseCode = ($responseCode == 0) ? $this->config['default redirect code'] : $responseCode;

        // flush everything and send the redirect response
        $this->flushAll()->header('Location: ' . $url, self::REPLACEALL)->responseCode($responseCode)->send($exit);
    }

    public function forceHttps(): void
    {
        logMsg('INFO', __METHOD__);

        if (!$this->input->isHttpsRequest()) {
            $this->redirect('https://' . $this->input->server('http_host') . $this->input->server('request_uri', $this->config['force http response code']));
        }
    }

    public function flushAll(): self
    {
        logMsg('INFO', __METHOD__);

        // flush headers & flush contents
        return $this->flushHeaders()->flush();
    }

    public function send(bool|int $exit = false): void
    {
        logMsg('INFO', __METHOD__);

        if (!$this->input->isCliRequest()) {
            // send headers
            foreach ($this->headers as $header) {
                $this->phpHeader($header);
            }
        }

        // this should be the only echo
        $this->phpEcho($this->output);

        if ($exit) {
            $exitCode = ($exit === true) ? 0 : $exit;
            $this->phpExit($exitCode);
        }
    }

    public function flush(): self
    {
        logMsg('INFO', __METHOD__);

        $this->output = '';

        return $this;
    }

    public function write(string $string, bool $append = true): self
    {
        logMsg('INFO', __METHOD__);

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

    public function contentType(string $type, string $fallback = ''): self
    {
        logMsg('INFO', __METHOD__ . ' ' . $type);

        // if they send in the shorthand content type convert it to a proper content type
        if (isset($this->mimes[$type])) {
            $contentType = $this->mimes[$type];
        } elseif (isset($this->mimes[$fallback])) {
            $contentType = $this->mimes[$fallback];
        } elseif (in_array($type, $this->mimes)) {
            $contentType = $type;
        } elseif (in_array($fallback, $this->mimes)) {
            $contentType = $fallback;
        } else {
            throw new OutputException('Unknown contentType(s) ' . $type . '/' . ($fallback ?? ''));
        }

        logMsg('INFO', __METHOD__ . ' ' . $contentType);

        $this->contentType = $contentType;

        $this->header($this->getContentTypeHeader($this->contentType, $this->charSet), self::REPLACEALL);

        return $this;
    }

    public function getContentType(): string
    {
        logMsg('INFO', __METHOD__);

        return $this->contentType;
    }

    public function charSet(string $charSet): self
    {
        logMsg('INFO', __METHOD__ . ' ' . $charSet);

        $this->charSet = $charSet;

        $this->header($this->getContentTypeHeader($this->contentType, $this->charSet), self::REPLACEALL);

        return $this;
    }

    public function getCharSet(): string
    {
        logMsg('INFO', __METHOD__);

        return $this->charSet;
    }

    public function header(string $value, int $replace = self::NO, bool $prepend = false): self
    {
        logMsg('INFO', __METHOD__ . ' ' . $value . ' ' . $replace . ' ' . $prepend);

        if ($replace != self::NO) {
            $splitOn = ($replace == self::REPLACEALL) ? '/(:| )/' : '/(;|=|,)/';
            $prefix = strtolower(preg_split($splitOn, $value)[0]);
            $prefixLength = strlen($prefix);

            foreach ($this->headers as $index => $headerValue) {
                if (substr(strtolower($headerValue), 0, $prefixLength) == $prefix) {
                    unset($this->headers[$index]);
                }
            }
        }

        if ($prepend) {
            array_unshift($this->headers, $value);
        } else {
            $this->headers[] = $value;
        }

        return $this;
    }

    public function getHeaders(): array
    {
        logMsg('DEBUG', __METHOD__);

        $headers = array_values($this->headers);

        logMsg('INFO', '', $headers);

        return $headers;
    }

    public function flushHeaders(): self
    {
        logMsg('INFO', __METHOD__);

        $this->headers = [];

        return $this;
    }

    public function responseCode(int|string $code): self
    {
        logMsg('DEBUG', __METHOD__, ['code' => $code]);

        if (is_string($code)) {
            $code = strtolower($code);

            if (!array_key_exists($code, $this->responseCodesInternalStringKeys)) {
                throw new OutputException('Unknown HTTP Status Code ' . $code);
            }

            $code = $this->responseCodesInternalStringKeys[$code];
        }

        // test the integer
        if (!isset($this->config['status codes'][$code])) {
            throw new OutputException('Unknown HTTP Status Code ' . (string)$code);
        }

        // code is valid integer
        $this->responseCode = $code;

        $this->header($this->getResponseHeader($this->responseCode), self::REPLACEALL, true);

        return $this;
    }

    public function getResponseCode(): int
    {
        logMsg('INFO', __METHOD__);

        return $this->responseCode;
    }

    /* internal */
    protected function getContentTypeHeader(string $contentType, string $charSet): string
    {
        logMsg('DEBUG', __METHOD__, ['contentType' => $contentType, 'charSet' => $charSet]);

        return 'Content-Type: ' . $contentType . '; charset=' . strtoupper($charSet);
    }

    protected function getResponseHeader(int $responseCode): string
    {
        logMsg('DEBUG', __METHOD__, ['responseCode' => $responseCode]);

        return $this->input->server('server_protocol', 'HTTP/1.0') . ' ' . $responseCode . ' ' . $this->config['status codes'][$responseCode];
    }

    /*
     * wrappers around the actual PHP functions which "send" output
     * you can override these to make unit testing easier
     * since this will allow you to capture the "sent" output
     */
    protected function phpEcho(string $string): void
    {
        echo $string;
    }

    protected function phpExit(int $status = 0): void
    {
        exit($status);
    }

    protected function phpHeader(string $header, bool $replace = false): void
    {
        header($header, $replace);
    }
}
