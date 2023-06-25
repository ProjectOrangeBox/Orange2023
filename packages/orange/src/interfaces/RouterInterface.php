<?php

declare(strict_types=1);

namespace dmyers\orange\interfaces;

interface RouterInterface
{
    const CONTROLLER = 0;
    const METHOD = 1;

    public function match(string $requestUri, string $requestMethod): self;
    public function getMatched(string $key = null): mixed; /* mixed string|array */
    public function getUrl(string $name, array $arguments = [], bool $appendSiteUrl = true): string;
    public function siteUrl(bool|string $appendHttp = true): string;
}
