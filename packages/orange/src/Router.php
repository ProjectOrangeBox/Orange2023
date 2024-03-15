<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\Singleton;
use orange\framework\exceptions\InvalidValue;
use orange\framework\exceptions\RouteNotFound;
use orange\framework\exceptions\ConfigNotFound;
use orange\framework\interfaces\RouterInterface;
use orange\framework\exceptions\RouterNameNotFound;

class Router extends Singleton implements RouterInterface
{
    private static ?RouterInterface $instance = null;
    protected string $siteUrl;
    protected bool $isHttps;
    protected array $routes = [];
    protected array $matched = [];
    protected array $config = [];
    protected array $getUrlSkip = [];

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    protected function __construct(array $config)
    {
        // load the default configs
        $this->config = mergeDefaultConfig($config, __DIR__ . '/config/routes.php', false);

        if (empty($this->config['site'])) {
            throw new ConfigNotFound('Route config "site" in routes.php can not be empty.');
        }

        $this->siteUrl = $this->config['site'];
        $this->isHttps = $this->config['isHttps'];
        $this->getUrlSkip = $this->config['getUrlSkip'];

        $this->addRoutes($this->config['routes']);
        $this->addRoutes($this->config['default routes']);

        $this->matched = [
            'request method' => null,
            'request uri' => null,
            'matched uri' => null,
            'matched method' => null,
            'url' => null,
            'argv' => null,
            'argc' => 0,
            'args' => 0,
            'name' => null,
            'callback' => null,
        ];
    }

    public static function getInstance(array $config): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public function addRoute(array $options): self
    {
        $this->routes[] = $options;

        return $this;
    }

    public function addRoutes(array $routes): self
    {
        foreach ($routes as $route) {
            $this->addRoute($route);
        }

        return $this;
    }

    public function match(string $requestUri, string $requestMethod): self
    {
        logMsg('DEBUG', __METHOD__ . ' uri:' . $requestUri . ' method:' . $requestMethod);

        $url = false;
        $requestMethod = strtoupper($requestMethod);

        // main loop
        foreach ($this->routes as $route) {
            if (isset($route['method'])) {
                $matchedMethod = (is_array($route['method'])) ? array_map('strtoupper', $route['method']) : [0 => strtoupper($route['method'])];

                // check if the current request method matches and the expression matches
                if ((in_array($requestMethod, $matchedMethod) || $route['method'] == '*') && preg_match("@^" . $route['url'] . "$@D", '/' . trim($requestUri, '/'), $argv)) {
                    // remove the first arg
                    $url = array_shift($argv);

                    // pop out of foreach loop
                    break;
                }
            }
        }

        if (!$url) {
            throw new RouteNotFound('[' . $requestMethod . ']' . $requestUri);
        }

        /* What is returned by getMatched() if no key is provided */
        $this->matched = [
            'request method' => $requestMethod,
            'request uri' => $requestUri,
            'matched uri' => $route['url'],
            'matched method' => $matchedMethod[0],
            'url' => $url,
            'argv' => $argv,
            'argc' => count($argv),
            'args' => (bool)count($argv),
            'name' => $route['name'] ?? null,
            'callback' => $route['callback'] ?? null,
        ];

        return $this;
    }

    public function getMatched(string $key = null): mixed /* mixed string|array */
    {
        if ($key != null && !\array_key_exists(strtolower($key), $this->matched)) {
            throw new InvalidValue('Unknown routing value "' . $key . '"');
        }

        return ($key) ? $this->matched[strtolower($key)] : $this->matched;
    }

    /*
     * convert route name to a path
     */
    public function getUrl(string $searchName, array $arguments = []): string
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
                            $matchedUrl = $record['url'];

                            foreach ($matches as $index => $match) {
                                $value = (string)$arguments[$index];

                                if (!in_array($value, $this->getUrlSkip)) {
                                    if (!preg_match('@' . $match[0] . '@m', $value)) {
                                        throw new InvalidValue('Parameter mismatch. Expecting ' . $match[1] . ' got ' . $value . ' route named "' . $searchName . '".');
                                    }
                                }

                                $matchedUrl = preg_replace('/' . preg_quote($match[0], '/') . '/', $value, $matchedUrl, 1);
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

        return $matchedUrl;
    }

    public function siteUrl(bool|string $appendHttp = true): string
    {
        $prefix = '';

        if ($appendHttp === true) {
            $s = ($this->isHttps) ? 's' : '';
            $prefix = ($appendHttp) ? 'http' . $s . '://' : '';
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
