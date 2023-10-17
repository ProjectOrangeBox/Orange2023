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
    public function show(string $template): void;
    public function show404(string $msg = null): void;
    public function show500(string $msg = null): void;
    public function onErrorsShow(string $template): void;
    public function has(): bool;
    public function errors(): array;
    public function collectErrors(object $object, string $method = 'errors'): self;
}
