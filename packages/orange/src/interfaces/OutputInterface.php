<?php

declare(strict_types=1);

namespace orange\framework\interfaces;

interface OutputInterface
{
    public const KEY = 1;
    public const VALUE = 2;
    public const SENT = 3;
    public const NAME = 4;
    public const OPTIONS = 5;

    public const HTML = 'text/html';
    public const JSON = 'application/json';
    public const JSONOPTIONS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;
    public const PRETTYJSONOPTIONS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

    public function write(string $string, bool $append = true): self;
    public function get(): string;
    public function flush(): self;

    public function header(string $key, string $value): self;
    public function getHeaders(): array;
    public function sendHeaders(): self;
    public function flushHeaders(): self;

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

    public function cookie(string|array $name, string $value = '', int $expire = 0, string $domain = '', string $path = '/', bool $secure = null, bool $httponly = null, string $samesite = null): self;
    public function removeCookie(string $name, string $domain = '', string $path = '/'): self;
    public function sendCookies(): self;
    public function flushCookies(): self;

    public function flushAll(): self;
    public function redirect(string $url, int $responseCode = 302, bool $exit = true): void;

    public function send(bool|int $exit = false): void;
}
