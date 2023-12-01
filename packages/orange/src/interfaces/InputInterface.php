<?php

declare(strict_types=1);

namespace dmyers\orange\interfaces;

interface InputInterface
{
    public function getUrl(int $component = -1);
    public function requestUri(): string;
    public function uriSegement(int $int): string;
    public function requestMethod(bool $lowercase = true): string;
    public function requestType(bool $lowercase = true): string;
    public function isAjaxRequest(): bool;
    public function isCliRequest(): bool;
    public function isHttpsRequest(bool $asString = false): mixed;

    // handle get, post, server, files, cookies, request, foo, bar
    // as long as it matches a config value sent in and is in 'valid input keys'
    public function __call(string $name, array $arguments): mixed;
    public function __get(string $name);
    public function withDefault($tempDefault): self;

    // get the most basic url based or body based input
    public function rawGet(): array;
    public function rawBody(): string;

    // returns ENTIRE input array
    public function copy(): array;

    // replaces input
    public function replace(array $input): self;
}
