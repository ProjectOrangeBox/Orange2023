<?php

declare(strict_types=1);

use orange\framework\exceptions\InvalidValue;
use orange\framework\interfaces\RouterInterface;

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

    public function getUrl(string $searchName, array $arguments = [], bool $appendSiteUrl = true, bool $overrideRegex = false): string
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

    public function addRoute(array $routes): self
    {
        return $this;
    }

    public function addRoutes(array $routes): self
    {
        return $this;
    }
}
