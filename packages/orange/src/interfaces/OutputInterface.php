<?php

declare(strict_types=1);

namespace dmyers\orange\interfaces;

interface OutputInterface
{
    const HTML = 'text/html';
    const JSON = 'application/json';
    const JSONOPTIONS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

    public function flush(): self;
    public function set(string $html): self;
    public function append(string $html): self;
    public function get(): string;
    public function send(): void;

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

    public function cookie(string|array $name, string $value = '', int $expire = 0, string $domain = '', string $path = '/', bool $secure = NULL, bool $httponly = NULL, string $samesite = NULL): self;
    public function flushCookies(): self;

    public function flushAll(): self;
    public function redirect(string $url, int $responseCode = 200, bool $exit = true): void;
    public function __debugInfo(): array;
}
