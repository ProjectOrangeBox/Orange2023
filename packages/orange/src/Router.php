<?php

declare(strict_types=1);

namespace dmyers\orange;

use dmyers\orange\exceptions\InvalidValue;
use dmyers\orange\exceptions\RouteNotFound;
use dmyers\orange\exceptions\ConfigNotFound;
use dmyers\orange\interfaces\RouterInterface;
use dmyers\orange\exceptions\RouterNameNotFound;

class Router implements RouterInterface
{
    private static RouterInterface $instance;
    protected string $siteUrl;
    protected bool $isHttps;
    // all routes
    protected array $routes = [];
    protected array $urls = [];
    // route args after a match
    protected array $matched = [];

    private function __construct(array $config)
    {
        if ($config['site'] == null) {
            throw new ConfigNotFound('Route config "site" in routes.php can not be empty.');
        }

        $this->siteUrl = $config['site'];
        $this->routes = $config['routes'];
        $this->isHttps = $config['isHttps'];
    }

    public static function getInstance(array $config): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public function match(string $requestUri, string $requestMethod): self
    {
        $url = false;
        $requestMethod = strtoupper($requestMethod);

        // main loop
        foreach ($this->routes as $route) {
            if (isset($route['method'])) {
                $matchedMethod = (is_array($route['method'])) ? strtoupper(implode('|', $route['method'])) : strtoupper($route['method']);

                // check if the current request method matches and the expression matches
                if ((strpos($matchedMethod, $requestMethod) !== false || $route['method'] == '*') && preg_match("@^" . $route['url'] . "$@D", '/' . trim($requestUri, '/'), $argv)) {
                    // remove the first arg
                    $url = array_shift($argv);

                    // pop out of foreach loop
                    break;
                }
            }
        }

        if (!$url) {
            throw new RouteNotFound();
        }

        $this->matched = [
            'requestMethod' => $requestMethod,
            'requestURI' => $requestUri,
            'matchedURI' => $route['url'],
            'matchedMethod' => $matchedMethod,
            'controller' => $route['callback'][self::CONTROLLER],
            'method' => $route['callback'][self::METHOD],
            'url' => $url,
            'argv' => $argv,
            'argc' => count($argv),
            'args' => (bool)count($argv),
        ];

        return $this;
    }

    public function getMatched(string $key = null): mixed /* mixed string|array */
    {
        if ($key != null && !isset($this->matched[$key])) {
            throw new InvalidValue('Unknown routing value "' . $key . '"');
        }

        return ($key) ? $this->matched[$key] : $this->matched;
    }

    public function getUrl(string $searchName, array $arguments = [], bool $appendSiteUrl = true): string
    {
        $matchedUrl = '';
        $searchName = $this->normalizeName($searchName);
        $argumentsCount = count($arguments);
        $matches = [];

        foreach ($this->routes as $record) {
            // do we have a name and url? with && if the first test is false the second isn't even tested
            if (isset($record['name']) && isset($record['url'])) {
                if ($this->normalizeName($record['name']) == $searchName) {
                    // make sure it didn't fail
                    if (preg_match_all('/\((.*?)\)/m', $record['url'], $matches, PREG_SET_ORDER, 0) !== false) {
                        $matchesCount = count($matches);

                        if ($argumentsCount != $matchesCount) {
                            throw new InvalidValue('Parameter count mismatch. Expecting ' . $matchesCount . ' got ' . $argumentsCount . ' route named "' . $searchName . '".');
                        }

                        if ($matchesCount > 0) {
                            foreach ($matches as $index => $match) {
                                $value = (string)$arguments[$index];

                                if (!preg_match('@' . $match[0] . '@m', $value)) {
                                    throw new InvalidValue('Parameter mismatch. Expecting ' . $match[1] . ' got ' . $value . ' route named "' . $searchName . '".');
                                }

                                $matchedUrl = str_replace($match[0], $value, $record['url']);
                            }
                        } else {
                            $matchedUrl = $record['url'];
                        }

                        // leave for loop on first solid match
                        break;
                    }
                }
            }
        }

        // if we are still empty then it's a complete fail
        if (empty($matchedUrl)) {
            throw new RouterNameNotFound('url route named "' . $searchName . '" not found');
        }

        return $this->siteUrl() . $matchedUrl;
    }

    public function siteUrl(bool|string $appendHttp = true): string
    {
        if ($appendHttp === true) {
            $prefix = ($appendHttp) ? 'http' . ($this->isHttps ? 's' : '') . '://' : '';
        } elseif (is_string($appendHttp)) {
            $prefix = $appendHttp;
        }

        return $prefix . $this->siteUrl;
    }

    protected function normalizeName(string $name): string
    {
        return mb_convert_case($name, MB_CASE_LOWER, mb_detect_encoding($name));
    }
}
