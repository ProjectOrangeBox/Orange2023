<?php

declare(strict_types=1);

namespace dmyers\orange;

use dmyers\orange\exceptions\Output as ExceptionsOutput;
use dmyers\orange\interfaces\OutputInterface;

class Output implements OutputInterface
{
    private static OutputInterface $instance;
    protected int $code = 200;
    protected string $contentType = '';
    protected string $charSet = '';
    protected array $headers = [];
    protected string $output = '';
    protected array $config = [];
    protected array $sentHeaders = [];
    protected int $sentCode = 0;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->contentType = $config['contentType'];
        $this->charSet = $config['charSet'];

        $this->header('Content-Type: ' . $this->contentType . '; charset=' . $this->charSet, 'Content-Type');
    }

    public static function getInstance(array $config): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public function flushOutput(): self
    {
        $this->output = '';

        return $this;
    }

    public function setOutput(?string $html): self
    {
        $this->output = ($html === null) ? '' : $html;

        return $this;
    }

    public function appendOutput(string $html): self
    {
        $this->output .= $html;

        return $this;
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function contentType(string $contentType): self
    {

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
        return $this->flushOutput()->flushHeaders();
    }

    public function sendHeaders(): self
    {
        $this->testIfHeadersSent('sent');

        if (empty($this->sentHeaders)) {
            foreach ($this->getHeaders() as $header) {
                header($header);
                $this->sentHeaders[] = $header;
            }
        }

        return $this;
    }

    protected function testIfHeadersSent(string $context): void
    {
        if (!empty($this->sentHeaders) && isset($this->config['show header error']) && $this->config['show header error'] === true) {
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

    public function responseCode(int $code): self
    {
        if ($this->sentCode != 0) {
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

        http_response_code($this->code);
        
        $this->sentCode = $this->code;

        return $this;
    }

    public function send(bool $exit = false): void
    {
        echo $this->sendResponseCode()->sendHeaders()->getOutput();

        if ($exit) {
            exit(0);
        }
    }

    public function redirect(string $url, int $responseCode = 302, bool $exit = true): void
    {
        $this->flushAll()->header('Location: ' . $url)->responseCode($responseCode)->send();

        if ($exit) {
            exit(0);
        }
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
            'output' => $this->output,
        ];
    }
}
