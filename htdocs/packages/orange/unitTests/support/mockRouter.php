<?php

declare(strict_types=1);

use dmyers\orange\exceptions\InvalidValue;
use dmyers\orange\interfaces\RouterInterface;

class mockRouter implements RouterInterface
{
    protected array $matched = [];

    public function __construct(array $config)
    {
        $this->matched = $config;
    }

    public function match(string $requestUri, string $requestMethod): self
    {
        return $this;
    }

    public function getMatched(string $key = null): mixed /* mixed string|array */
    {
        if ($key != null && !\array_key_exists(strtolower($key), $this->matched)) {
            throw new InvalidValue('Unknown routing value "' . $key . '"');
        }

        return ($key) ? $this->matched[strtolower($key)] : $this->matched;
    }

    public function getUrl(string $searchName, array $arguments = [], bool $appendSiteUrl = true): string
    {
        return '';
    }

    public function siteUrl(bool|string $appendHttp = true): string
    {
        return '';
    }

    public function __debugInfo(): array
    {
        return [];
    }
}
