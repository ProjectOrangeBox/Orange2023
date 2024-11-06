<?php

declare(strict_types=1);

namespace orange\framework\interfaces;

interface InputInterface
{
    // HTTP Methods
    public const GET       = 'GET';
    public const POST      = 'POST';
    public const PUT       = 'PUT';
    public const DELETE    = 'DELETE';
    public const HEAD      = 'HEAD';
    public const OPTIONS   = 'OPTIONS';
    public const TRACE     = 'TRACE';
    public const CONNECT   = 'CONNECT';

    public function getUrl(int $component = -1);
    public function requestUri(): string;
    public function uriSegment(int $segmentNumber): string;

    public function requestMethod(bool $asLowercase = true): string;
    public function requestType(bool $asLowercase = true): string;

    public function isAjaxRequest(): bool;
    public function isCliRequest(): bool;
    public function isHttpsRequest(bool $asString = false): bool|string;
    //public function forceHttps(): void;

    // handle get, post, server, files, cookies, request, foo, bar
    // as long as it matches a config value sent in and is in 'valid input keys'
    // $value = $input->request('keyname', true);
    public function __call(string $name, array $arguments): mixed;
    public function __get(string $name);
    public function __isset(string $name): bool;

    public function has(string $name, string $key = null): bool;

    // get the most basic url based or body based input
    public function rawGet(): string;
    public function rawBody(): string;

    // returns ENTIRE input array
    public function copy(): array;

    // replaces input
    public function replace(array $input): self;
}
