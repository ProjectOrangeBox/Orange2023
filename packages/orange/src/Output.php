<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\interfaces\OutputInterface;
use orange\framework\exceptions\output\Output as OutputException;

class Output extends Singleton implements OutputInterface
{
    use ConfigurationTraits;

    protected array $mimes;
    protected string $output;
    protected int $statusCode;

    protected array $statusCodesInt;
    protected array $statusCodes;
    protected array $statusCodesNormalized;
    protected bool $statusCodeSent;

    protected string $contentType;
    protected string $charSet;
    protected string $language;
    protected array $headers;

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    protected function __construct(array $config)
    {
        $this->config = $this->mergeWithDefault($config, 'output');

        // handle mimes merging
        $mimes = require __DIR__ . '/config/mimes.php';

        $this->config['mimes'] = (isset($this->config['mimes'])) ? array_replace_recursive($this->config['mimes'], $mimes) : $mimes;

        $this->mimes = $this->config['mimes'];

        // handle http status code merging
        $statusCodesInt = require __DIR__ . '/config/statusCodes.php';

        $this->config['status codes'] = (isset($this->config['status codes'])) ? array_replace_recursive($this->config['status codes'], $statusCodesInt) : $statusCodesInt;

        $this->statusCode = 200;

        $this->output = '';

        $this->headers = [];

        $this->statusCodesInt = $this->config['status codes'];

        $this->statusCodes = array_flip($this->statusCodesInt);
        $this->statusCodesNormalized = array_change_key_case($this->statusCodes, CASE_LOWER);

        $this->statusCodeSent = false;

        $this->contentType = $this->config['contentType'];
        $this->charSet = $this->config['charSet'];
        $this->language = $this->config['language'];
    }

    public function flushAll(): self
    {
        return $this->flush()->flushHeaders();
    }

    public function send(bool|int $exit = false): void
    {
        $this->sendResponseCode()->sendHeaders();

        // this should be the only echo
        $this->echo($this->output);

        if ($exit) {
            $exitCode = ($exit === true) ? 0 : $exit;
            $this->exit($exitCode);
        }
    }

    public function redirect(string $url, int $responseCode = 302, bool $exit = true): void
    {
        log::msg('DEBUG', __METHOD__ . ' ' . $url . ' ' . (string) $responseCode . ' ' . (string)$exit);

        // flush everything and send the redirect response
        $this->flushAll()->header('Location: ' . $url)->responseCode($responseCode)->send($exit);
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

    public function header(string $value, bool $replace = true): self
    {
        $this->headers[] = [
            self::VALUE => $value,
            self::REPLACE => $replace,
            self::SENT => false,
        ];

        return $this;
    }

    public function getHeaders(): array
    {
        // just the headers
        $headers = [];

        foreach ($this->headers as $header) {
            $headers[] = $header[self::VALUE];
        }

        return $headers;
    }

    public function removeHeaders(string $regex): self
    {
        foreach ($this->headers as $index => $header) {
            if (preg_match_all($regex, $header[self::VALUE], $matches, PREG_SET_ORDER, 0) > 0) {
                unset($this->headers[$index]);
            }
        }

        return $this;
    }

    public function flushHeaders(): self
    {
        foreach ($this->headers as $header) {
            if ($header[self::SENT]) {
                throw new OutputException('Some headers already sent.');
            }
        }

        $this->headers = [];

        return $this;
    }

    public function sendHeaders(): self
    {
        if ($this->headersSent()) {
            throw new OutputException('Output already started.');
        }

        // add our content type
        $this->header('Content-Type: ' . $this->contentType . '; charset=' . $this->charSet);
        $this->header('Content-Language: ' . $this->language);

        // send headers
        foreach ($this->headers as $index => $header) {
            if (!$header[self::SENT]) {
                $this->sendHeader($header[self::VALUE], $header[self::REPLACE]);

                // flip send flag
                $this->headers[$index][self::SENT] = true;
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

    public function language(string $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function responseCode(int|string $code): self
    {
        if ($this->statusCodeSent) {
            throw new OutputException('Status response code sent.');
        }

        if (is_string($code)) {
            $code = strtolower($code);

            if (!array_key_exists($code, $this->statusCodesNormalized)) {
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
        $this->httpResponseCode($this->statusCode);

        $this->statusCodeSent = true;

        return $this;
    }

    /*
     * wrappers around the actual PHP functions which "send" output
     * you can override these to make unit testing easier
     * since this will allow you to capture the "sent" output
     */
    protected function echo(string $string): void
    {
        echo $string;
    }

    protected function exit(int $status = 0): void
    {
        exit($status);
    }

    protected function headersSent(): bool
    {
        return headers_sent();
    }

    protected function sendHeader(string $header, bool $replace = false): void
    {
        header($header, $replace);
    }

    protected function httpResponseCode(int $response_code = 0): void
    {
        http_response_code($response_code);
    }
}
