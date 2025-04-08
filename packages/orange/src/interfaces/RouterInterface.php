<?php

declare(strict_types=1);

namespace orange\framework\interfaces;

interface RouterInterface
{
    public function match(string $requestUri, string $requestMethod): self;
    public function getMatched(?string $key = null): mixed; /* mixed string|array */
    public function getUrl(string $searchName, array $arguments = []): string;
    public function siteUrl(bool|string $appendHttp = true): string;
    public function addRoute(array $route): self;
    public function addRoutes(array $routes): self;
}
