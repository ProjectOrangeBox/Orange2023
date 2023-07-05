<?php

declare(strict_types=1);

namespace dmyers\orange\interfaces;

interface ErrorInterface
{
    public function requestType(string $requestType): self;
    public function add(mixed $value, string $key = null): self;
    public function collectErrors(object $object, string $key = null): self;
    public function clear(string $key): self;
    public function reset(): self;
    public function has(?string $key = null): bool;
    public function errors(?string $key = null): mixed;
    public function send(int|string $view = null, int $code = 0, ?string $key = null, ?string $requestType = null): void;
    public function sendOnError(int|string $view = null, int $code = 0, ?string $key = null, ?string $requestType = null): void;
    public function showError(string $message, int $code = 0, string $heading = 'An Error Was Encountered', string $view = null, array $override = []): void;
    public function display(int|string $view, array $data, int $code = 0, array $override = []): void;
    public function __debugInfo(): array;
}
