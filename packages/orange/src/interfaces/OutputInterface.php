<?php

declare(strict_types=1);

namespace dmyers\orange\interfaces;

interface OutputInterface
{
    const HTML = 'text/html';
    const JSON = 'application/json';
    const JSONOPTIONS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

    public function flushOutput(): self;
    public function setOutput(?string $html): self;
    public function appendOutput(string $html): self;
    public function getOutput(): string;
    public function contentType(string $contentType): self;
    public function getContentType(): string;
    public function header(string $header, string $key = null): self;
    public function getHeaders(): array;
    public function sendHeaders(): self;
    public function flushHeaders(): self;
    public function charSet(string $charSet): self;
    public function getCharSet(): string;
    public function responseCode(int $code): self;
    public function getResponseCode(): int;
    public function sendResponseCode(): self;
    public function send(): void;
    public function redirect(string $url, int $responseCode = 200, bool $exit = true): void;
    public function flushAll(): self;
}
