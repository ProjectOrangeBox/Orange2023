<?php

declare(strict_types=1);

namespace dmyers\orange\interfaces;

interface ErrorInterface
{
    public function responseCode(int $statusCode): self;
    public function requestType(string $requestType): self;
    public function mimeType(string $mimeType): self;
    public function charSet(string $charSet): self;
    public function clear(): self;
    public function reset(): self;
    public function folder(string $folder): self;
    public function add(): self;
    public function send(string $view): void;
    public function onErrorsSend(string $view): void;
    public function send404(string $msg = null): void;
    public function send500(string $msg = null): void;
    public function has(): bool;
    public function errors(): array;
}
