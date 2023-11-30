<?php

declare(strict_types=1);

namespace dmyers\orange\interfaces;

interface RouterInterface
{
    public function match(string $requestUri, string $requestMethod): self;
    public function getMatched(string $key = null): mixed; /* mixed string|array */
    public function getUrl(string $searchName, array $arguments = [], bool $appendSiteUrl = true, bool $overrideRegex = false): string;
    public function siteUrl(bool|string $appendHttp = true): string;
}
