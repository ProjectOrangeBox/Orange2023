<?php

declare(strict_types=1);

namespace dmyers\orange\interfaces;

interface InputInterface
{
    public function requestUri(): string;
    public function uriSegement(int $int): string;
    public function requestMethod(bool $lowercase = true): string;
    public function requestType(bool $lowercase = true): string;
    public function isAjaxRequest(): bool;
    public function isCliRequest(): bool;
    public function isHttpsRequest(bool $asString = false): mixed;

    public function body($name = null, $default = null): mixed;
    public function get(?string $name = null, $default = null): mixed;

    public function extract(string $type, ?string $name = null, $default = null);

    public function server(string $name = null, $default = null): mixed;
    public function file(string $name = null, $default = null): mixed;
    public function cookie(string $name = null, $default = null): mixed;

    public function copy(): array;
    public function replace(array $input): self;
}
