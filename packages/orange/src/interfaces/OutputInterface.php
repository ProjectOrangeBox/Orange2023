<?php

declare(strict_types=1);

namespace orange\framework\interfaces;

interface OutputInterface
{
    public const VALUE = 1;
    public const SENT = 2;
    public const NAME = 3;
    public const OPTIONS = 4;
    public const REPLACE = 5;

    public const HTML = 'text/html';
    public const JSON = 'application/json';
    public const JSONOPTIONS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;
    public const PRETTYJSONOPTIONS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

    public function write(string $string, bool $append = true): self;
    public function get(): string;
    public function flush(): self;

    public function header(string $value, bool $replace = false): self;
    public function getHeaders(): array;
    public function sendHeaders(): self;
    public function flushHeaders(): self;
    public function removeHeaders(string $regex): self;

    // since these are header values, these are sent when sendHeaders() is called
    public function contentType(string $contentType): self;
    public function getContentType(): string;

    public function charSet(string $charSet): self;
    public function getCharSet(): string;
    public function language(string $language): self;

    // their is always a response code so it starts as 200 (default)
    // and you simply change it
    public function responseCode(int $code): self;
    public function getResponseCode(): int;
    public function sendResponseCode(): self;

    public function flushAll(): self;
    public function redirect(string $url, int $responseCode = 302, bool $exit = true): void;

    public function send(bool|int $exit = false): void;
}
