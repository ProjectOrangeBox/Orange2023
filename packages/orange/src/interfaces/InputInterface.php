<?php

declare(strict_types=1);

namespace dmyers\orange\interfaces;

interface InputInterface
{
    public function requestUri(): string;
    public function uriSegement(int $int): string;
    public function requestMethod(): string;
    public function requestType(): string;
    public function isAjaxRequest(): bool;
    public function isCliRequest(): bool;
    public function isHttpsRequest(): bool;

    public function raw(): mixed;
    public function rawObj(): mixed;
    public function rawp(string $name = null, $default = null): mixed;

    public function post(string $name = null, $default = null): mixed;
    public function get(string $name = null, $default = null): mixed;

    public function request(string $name = null, $default = null): mixed;

    public function server(string $name = null, $default = null): mixed;
    public function file(string $name = null, $default = null): mixed;

    public function copy(): array;
    public function replace(array $input): self;
}
