<?php

declare(strict_types=1);

namespace orange\framework;

use peels\cache\CacheInterface;
use orange\framework\base\Singleton;
use orange\framework\exceptions\InvalidValue;
use orange\framework\interfaces\InputInterface;
use orange\framework\traits\ConfigurationTrait;
use orange\framework\exceptions\MissingRequired;
use orange\framework\interfaces\RouterInterface;
use orange\framework\exceptions\router\RouteNotFound;
use orange\framework\exceptions\router\RouterNameNotFound;

/**
 * Class Router
 *
 * Manages route definitions, matching requests to routes, and generating URLs from route names.
 * Implements Singleton and RouterInterface patterns.
 *
 * Key Responsibilities:
 * - Adding individual routes or groups of routes.
 * - Matching incoming HTTP requests to defined routes.
 * - Generating URLs based on route names and arguments.
 * - Handling configuration for routing rules and site URLs.
 *
 * @package orange\framework
 */
class Router extends Singleton implements RouterInterface
{
    /** include ConfigurationTrait methods */
    use ConfigurationTrait;

    /**
     * Provides access to input-related utilities (e.g., HTTP method, request URI).
     */
    protected InputInterface $input;

    /**
     * Base URL of the site, used for generating full URLs.
     */
    protected string $siteUrl = '';

    /**
     * List of all registered routes.
     */
    protected array $routes = [];

    /**
     * Stores information about the last matched route.
     */
    protected array $matched = [];

    /**
     * Determines whether URL validation during generation can be skipped.
     */
    protected bool $skipCheckingType = false;

    /**
     * Array of routes sorted by the route name
     */
    protected array $routesByName = [];

    protected array $matchAll = [];

    /**
     * Protected constructor to enforce Singleton usage.
     *
     * @param array $config Configuration array for routing settings.
     * @param InputInterface $input Provides request-related data.
     * @throws MissingRequired If the 'site' configuration is missing.
     */
    protected function __construct(array $config, InputInterface $input, ?CacheInterface $cache = null)
    {
        logMsg('INFO', __METHOD__);

        // load the default configs
        $this->config = $this->mergeWithDefault($config, false, 'routes');

        if (empty($this->config['site'])) {
            throw new MissingRequired('Route config "site" in routes.php can not be empty.');
        }

        $this->input = $input;

        $this->siteUrl = $this->config['site'];
        $this->skipCheckingType = $this->config['skip checking type'];
        $this->matchAll = $this->config['match all'];

        // if cache is supplied then use it
        if ($cache) {
            if (!$routes = $cache->get(__CLASS__)) {
                $this->loadRoutes();
                $cache->set(__CLASS__, ['routes' => $this->routes, 'routesByName' => $this->routesByName]);
            } else {
                $this->routes = $routes['routes'];
                $this->routesByName = $routes['routesByName'];
            }
        } else {
            $this->loadRoutes();
        }

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

    protected function loadRoutes(): void
    {
        // add 404 first which makes it the last in the search
        $this->addRoute($this->config['404']);

        // add our default home - this could get overwritten by another home
        $this->addRoute($this->config['home']);

        // add the user supplied routes
        $this->addRoutes($this->config['routes']);
    }

    /**
     * Adds a single route definition.
     *
     * @param array $options Route configuration (e.g., method, URL pattern, callback).
     * @return self
     */
    public function addRoute(array $options): self
    {
        logMsg('DEBUG', __METHOD__, $options);

        if (isset($options['method'])) {
            $methods = $options['method'] == '*' ? $this->matchAll : (array)$options['method'];

            foreach ($methods as $method) {
                $methodUpper = mb_strtoupper($method);

                $this->routes[$methodUpper] = $this->routes[$methodUpper] ?? [];

                // FILO stack
                array_unshift($this->routes[$methodUpper], $options);
            }
        }

        if (isset($options['name'])) {
            $this->routesByName[mb_strtolower($options['name'])] = $options;
        }

        return $this;
    }

    /**
     * Adds multiple routes in bulk.
     *
     * @param array $routes Array of route configurations.
     * @return self
     */
    public function addRoutes(array $routes): self
    {
        logMsg('INFO', __METHOD__);
        logMsg('INFO', 'Routes ' . count($routes));

        // put them in the array the same way they where sent in - top to bottom
        foreach (array_reverse($routes) as $route) {
            $this->addRoute($route);
        }

        return $this;
    }

    /**
     * Matches a request URI and method to a defined route.
     *
     * @param string $requestUri The request URI to match.
     * @param string $requestMethod The HTTP request method (e.g., GET, POST).
     * @return self
     * @throws RouteNotFound If no matching route is found.
     */
    public function match(string $requestUri, string $requestMethod): self
    {
        logMsg('DEBUG', __METHOD__, compact('requestUri', 'requestMethod'));

        $requestMethodUpper = mb_strtoupper($requestMethod);

        foreach ($this->routes[$requestMethodUpper] ?? [] as $route) {
            // if the route doesn't have a method or url then just skip it
            if (!isset($route['url'])) {
                continue;
            }

            if (preg_match("@^" . $route['url'] . "$@D", '/' . trim($requestUri, '/'), $argv)) {
                $url = array_shift($argv);

                $this->matched = [
                    'request method' => $requestMethodUpper,
                    'request uri' => $requestUri,
                    'matched uri' => $route['url'],
                    'matched method' => $route['method'],
                    'url' => $url,
                    'argv' => $argv,
                    'argc' => count($argv),
                    'args' => !empty($argv),
                    'name' => $route['name'] ?? null,
                    'callback' => $route['callback'] ?? null,
                ];

                logMsg('DEBUG', 'Route matched.', $this->matched);

                // we found a match break from foreach loop
                break;
            }
        }

        // every route has a url. (why else would you add it?)
        if (!$this->matched['url']) {
            throw new RouteNotFound("[$requestMethod] $requestUri");
        }

        return $this;
    }

    /**
     * Retrieves matched route information.
     *
     * @param string|null $key Specific key to retrieve (e.g., 'url', 'method').
     * @return mixed The value of the matched key or all matched data.
     * @throws InvalidValue If an invalid key is requested.
     */
    public function getMatched(string $key = null): mixed /* mixed string|array */
    {
        logMsg('DEBUG', __METHOD__, ['key' => $key]);

        if ($key != null && !\array_key_exists(strtolower($key), $this->matched)) {
            throw new InvalidValue('Unknown routing value "' . $key . '"');
        }

        return ($key) ? $this->matched[strtolower($key)] : $this->matched;
    }

    /**
     * Generates a URL from a named route and arguments.
     *
     * @param string $searchName Route name.
     * @param array $arguments Arguments for dynamic segments.
     * @return string The generated URL.
     * @throws RouterNameNotFound If the route name is not found.
     */
    public function getUrl(string $searchName, array $arguments = []): string
    {
        logMsg('INFO', __METHOD__ . ' ' . $searchName);
        logMsg('DEBUG', '', ['searchName' => $searchName, 'arguments' => $arguments]);

        $lowercaseSearchName = mb_strtolower($searchName);

        if (!isset($this->routesByName[$lowercaseSearchName])) {
            throw new RouterNameNotFound($searchName);
        }

        if (!isset($this->routesByName[$lowercaseSearchName]['url'])) {
            throw new InvalidValue('missing "url" for route named ' . $searchName);
        }

        // let's begin
        $url = $this->routesByName[$lowercaseSearchName]['url'];

        $matches = [];

        // merge the arguments with the available parameters
        $hasArgs = preg_match_all('/\((.*?)\)/m', $url, $matches, PREG_SET_ORDER, 0);

        // do the number of arguments passed in match the number of arguments in the url?
        if (count($matches) != count($arguments)) {
            throw new InvalidValue('Parameter count mismatch. Expecting ' . count($matches) . ' got ' . count($arguments) . ' route named "' . $searchName . '".');
        }

        // ok let's start off with the url
        $matchedUrl = $url;

        // does this url have any arguments?
        if ($hasArgs) {
            foreach ($matches as $index => $match) {
                // convert to a string
                $value = (string)$arguments[$index];

                // make sure the argument matches the regular expression for that segement
                if (!$this->skipCheckingType && !preg_match('@' . $match[0] . '@m', $value)) {
                    throw new InvalidValue('Parameter mismatch. Expecting ' . $match[1] . ' got ' . $value);
                }

                // replace the segement with the passed argument
                $matchedUrl = preg_replace('/' . preg_quote($match[0], '/') . '/', $value, $matchedUrl, 1);
            }
        }

        // is the matchedUrl now empty?
        if (empty($matchedUrl)) {
            throw new RouterNameNotFound($searchName);
        }

        logMsg('INFO', __METHOD__ . ' matched Url ' . $matchedUrl);

        return $matchedUrl;
    }

    /**
     * Generates the site's base URL, optionally with an HTTP/HTTPS prefix.
     *
     * This method allows the caller to:
     * - Include or exclude the HTTP/HTTPS prefix.
     * - Manually specify a custom prefix.
     *
     * @param bool|string $appendHttp
     *      - `true`: Automatically determines `http` or `https` based on the request.
     *      - `false`: Returns only the base URL without any protocol prefix.
     *      - `string`: Allows specifying a custom protocol prefix (e.g., `'ftp://'`).
     *
     * @return string The generated base URL with the specified prefix.
     */
    public function siteUrl(bool|string $prefix = true): string
    {
        if (is_string($prefix)) {
            $scheme = $prefix;
        } else {
            $scheme = ($this->input->isHttpsRequest() ? 'https://' : 'http://');
        }

        return $prefix ? $scheme . $this->siteUrl : $this->siteUrl;
    }
}
