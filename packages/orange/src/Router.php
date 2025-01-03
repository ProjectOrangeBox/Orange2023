<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\base\Singleton;
use orange\framework\exceptions\InvalidValue;
use orange\framework\interfaces\InputInterface;
use orange\framework\traits\ConfigurationTrait;
use orange\framework\exceptions\MissingRequired;
use orange\framework\interfaces\RouterInterface;
use orange\framework\exceptions\router\RouteNotFound;
use orange\framework\exceptions\router\RouterNameNotFound;

class Router extends Singleton implements RouterInterface
{
    use ConfigurationTrait;

    protected InputInterface $input;

    protected string $siteUrl = '';
    protected array $routes = [];
    protected array $matched = [];
    protected bool $getUrlSkip = false;
    protected string $matchedUrl = '';

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    protected function __construct(array $config, InputInterface $input)
    {
        logMsg('INFO', __METHOD__);

        // load the default configs
        $this->config = $this->mergeWithDefault($config, false, 'routes');

        if (empty($this->config['site'])) {
            throw new MissingRequired('Route config "site" in routes.php can not be empty.');
        }

        $this->input = $input;

        $this->routes = [];

        $this->siteUrl = $this->config['site'];
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

    public function addRoute(array $options): self
    {
        logMsg('DEBUG', __METHOD__, $options);

        $this->routes[] = $options;

        return $this;
    }

    public function addRoutes(array $routes): self
    {
        logMsg('INFO', __METHOD__);
        logMsg('INFO', 'Routes ' . count($routes));

        foreach ($routes as $route) {
            $this->addRoute($route);
        }

        return $this;
    }

    public function match(string $requestUri, string $requestMethod): self
    {
        logMsg('DEBUG', __METHOD__, ['requestUri' => $requestUri, 'requestMethod' => $requestMethod]);

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

        logMsg('DEBUG', 'matched', $this->matched);

        return $this;
    }

    public function getMatched(string $key = null): mixed /* mixed string|array */
    {
        logMsg('DEBUG', __METHOD__, ['key' => $key]);

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
        logMsg('INFO', __METHOD__ . ' ' . $searchName);
        logMsg('DEBUG', '', ['searchName' => $searchName, 'arguments' => $arguments]);

        $this->matchedUrl = '';

        $searchName = $this->normalize($searchName);

        foreach ($this->routes as $record) {
            $matches = [];

            // do we have a name and url? with && if the first test is false the second isn't even tested
            if (isset($record['name'], $record['url']) && $this->normalize($record['name']) == $searchName && preg_match_all('/\((.*?)\)/m', $record['url'], $matches, PREG_SET_ORDER, 0) !== false) {
                $this->matchedUrl = $this->processMatchedUrl($searchName, $arguments, $record['url'], $matches);

                // leave for loop on first solid match
                break;
            }
        }

        // if we are still empty then it's a complete fail
        if (empty($this->matchedUrl)) {
            throw new RouterNameNotFound('url route named "' . $searchName . '" not found');
        }

        logMsg('INFO', __METHOD__ . ' matched Url ' . $this->matchedUrl);

        return $this->matchedUrl;
    }

    public function siteUrl(bool|string $appendHttp = true): string
    {
        logMsg('INFO', __METHOD__ . ' ' . $appendHttp);

        $prefix = '';

        if ($appendHttp === true) {
            $s = $this->input->isHttpsRequest() ? 's' : '';
            $prefix = ($appendHttp) ? 'http' . $s . '://' : '';
        } elseif (is_string($appendHttp)) {
            $prefix = $appendHttp;
        }

        $complete = $prefix . $this->siteUrl;

        logMsg('INFO', $complete);

        return $complete;
    }

    protected function processMatchedUrl(string $searchName, array $arguments, string $matchedUrl, array $matches): string
    {
        logMsg('INFO', __METHOD__ . ' search ' . $searchName);
        logMsg('DEBUG', '', ['searchName' => $searchName, 'arguments' => $arguments, 'matchedUrl' => $matchedUrl, 'matches' => $matches]);

        $matchesCount = count($matches);

        if (count($arguments) != $matchesCount) {
            throw new InvalidValue('Parameter count mismatch. Expecting ' . $matchesCount . ' got ' . count($arguments) . ' route named "' . $searchName . '".');
        }

        if ($matchesCount > 0) {
            foreach ($matches as $index => $match) {
                $value = (string)$arguments[$index];

                // make sure the argument matches the regular expression for that segement
                if (!$this->getUrlSkip && !preg_match('@' . $match[0] . '@m', $value)) {
                    throw new InvalidValue('Parameter mismatch. Expecting ' . $match[1] . ' got ' . $value . ' route named "' . $searchName . '".');
                }

                // replace the segement with the passed argument
                $matchedUrl = preg_replace('/' . preg_quote($match[0], '/') . '/', $value, $matchedUrl, 1);
            }
        }

        logMsg('INFO', __METHOD__ . ' match ' . $matchedUrl);

        return $matchedUrl;
    }
}
