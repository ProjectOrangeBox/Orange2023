<?php

declare(strict_types=1);

namespace dmyers\orange\stubs;

use dmyers\orange\interfaces\OutputInterface;

class Output implements OutputInterface
{
    protected static OutputInterface $instance;

    private function __construct(array $config)
    {
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
        return $this;
    }

    public function setOutput(?string $html): self
    {
        return $this;
    }

    public function appendOutput(string $html): self
    {
        return $this;
    }

    public function getOutput(): string
    {
        return '';
    }

    public function contentType(string $contentType): self
    {
        return $this;
    }

    public function getContentType(): string
    {
        return '';
    }

    public function header(string $header, string $key = null): self
    {
        return $this;
    }

    public function getHeaders(): array
    {
        return [];
    }

    public function flushHeaders(): self
    {
        return $this;
    }

    public function flushAll(): self
    {
        return $this->flushOutput()->flushHeaders();
    }

    public function sendHeaders(): self
    {
        return $this;
    }

    public function charSet(string $charSet): self
    {
        return $this;
    }

    public function getCharSet(): string
    {
        return '';
    }

    public function responseCode(int $code): self
    {
        return $this;
    }

    public function getResponseCode(): int
    {
        return 0;
    }

    public function sendResponseCode(): self
    {
        return $this;
    }

    public function send(bool $exit = false): void
    {
    }

    public function redirect(string $url, int $responseCode = 200, bool $exit = true): void
    {
    }
}
