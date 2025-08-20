<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\base\Singleton;
use orange\framework\exceptions\InvalidValue;
use orange\framework\interfaces\CacheInterface;
use orange\framework\interfaces\InputInterface;
use orange\framework\traits\ConfigurationTrait;
use orange\framework\exceptions\MissingRequired;
use orange\framework\interfaces\RouterInterface;
use orange\framework\exceptions\router\RouteNotFound;
use orange\framework\exceptions\router\RouterNameNotFound;
use orange\framework\exceptions\router\HttpMethodNotSupported;

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
    // include ConfigurationTrait methods
    use ConfigurationTrait;

    // Provides access to input-related utilities (e.g., HTTP method, request URI).
    protected InputInterface $input;

    // Base URL of the site, used for generating full URLs.
    protected string $siteUrl = '';

    // Routes by HTTP method
    protected array $routes = [
        'CONNECT' => [],
        'DELETE' => [],
        'GET' => [],
        'HEAD' => [],
        'OPTIONS' => [],
        'PATCH' => [],
        'POST' => [],
        'PUT' => [],
        'TRACE' => [],
    ];

    // Stores information about the last matched route.
    protected array $matched = [];

    // Determines whether URL validation during generation can be skipped.
    protected bool $skipParameterTypeChecking = false;

    // Array of routes sorted by the route name
    protected array $routesByName = [];

    // On Match All routes use these methods
    // e.g., ['GET', 'POST', 'PUT', 'DELETE']
    protected array $onMatchAll = [];

    // if cache passed this is an reference to it
    protected ?CacheInterface $cache;
    // the caching key for routes
    protected string $cacheKey;
    // to turn off caching of routes
    protected bool $disableCaching = false;

    /**
     * Protected constructor to enforce Singleton usage.
     *
     * @param array $config Configuration array for routing settings.
     * @param InputInterface $input Provides request-related data.
     * @param CacheInterface $cache optional cache service
     * @throws MissingRequired If the 'site' configuration is missing.
     */
    protected function __construct(array $config, InputInterface $input, ?CacheInterface $cache = null)
    {
        logMsg('INFO', __METHOD__);

        // load the default configs
        $this->config = $this->mergeConfigWith($config, 'routes', false);

        // Validate the configuration
        if (empty($this->config['site'])) {
            throw new MissingRequired('Route config "site" in routes.php can not be empty.');
        }

        $this->input = $input;
        // Set the site URL
        $this->siteUrl = $this->config['site'];
        // Set the skip parameter type checking flag
        $this->skipParameterTypeChecking = $this->config['skip parameter type checking'];
        // Set the on match all methods
        $this->onMatchAll = $this->config['match all'];

        // Set the cache if provided
        if ($this->cache = $cache) {
            // Set the cache key
            $this->cacheKey = ENVIRONMENT . '\\' . __CLASS__;
        }

        // Load the routes
        $this->loadRoutes();

        // setup the "empty" matched
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

    /**
     * Adds a single route definition.
     *
     * @param array $options Route configuration (e.g., method, URL pattern, callback).
     * @return self
     */
    public function addRoute(array $options): self
    {
        logMsg('DEBUG', __METHOD__, $options);

        // can't do anything without a url
        if (isset($options['url'])) {
            // is this a http routable method?
            if (isset($options['method'])) {
                // is this the wildcard all an array or a single value
                $methods = $options['method'] == '*' ? $this->onMatchAll : (array)$options['method'];

                // for each method add it to the appropriate array for quicker access
                foreach ($methods as $method) {
                    $upperMethod = strtoupper($method);

                    if (!isset($this->routes[$upperMethod])) {
                        throw new HttpMethodNotSupported($method);
                    }

                    // FILO stack
                    array_unshift($this->routes[$upperMethod], $options);
                }
            }

            // does this route have a name to use with get url?
            if (isset($options['name'])) {
                // add it to the array by name
                $this->routesByName[strtolower($options['name'])] = $options['url'];
            }
        }

        // Save the route to cache
        $this->saveCache();

        // return $ instance for method chaining
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

        // disable cache temporarily to avoid writing to cache while adding routes
        $this->disableCaching = true;

        // Add each route in reverse order
        foreach (array_reverse($routes) as $route) {
            $this->addRoute($route);
        }
        // re-enable cache after adding routes
        $this->disableCaching = false;

        // save the routes to cache if available
        $this->saveCache();

        // return $ instance for method chaining
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

        // Normalize the request method
        $requestMethodUpper = mb_strtoupper($requestMethod);

        // Check for matching routes
        foreach ($this->routes[$requestMethodUpper] ?? [] as $route) {
            // Check if the route matches the request URI
            if (preg_match("@^" . $route['url'] . "$@D", '/' . trim($requestUri, '/'), $argv)) {
                // Get the URL from the arguments
                $url = array_shift($argv);

                // Set the matched route information
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
    public function getMatched(?string $key = null): mixed /* mixed string|array */
    {
        logMsg('DEBUG', __METHOD__, ['key' => $key]);

        // Check if the key is valid
        if ($key != null && !\array_key_exists(strtolower($key), $this->matched)) {
            throw new InvalidValue('Unknown routing value "' . $key . '"');
        }

        // Return the matched data
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
    public function getUrl(string $searchName, array $arguments = [], ?bool $skipParameterTypeChecking = null): string
    {
        logMsg('INFO', __METHOD__ . ' ' . $searchName);
        logMsg('DEBUG', '', ['searchName' => $searchName, 'arguments' => $arguments, 'skipParameterTypeChecking' => $skipParameterTypeChecking]);

        // Normalize the search name
        $lowercaseSearchName = mb_strtolower($searchName);

        // Check if the route exists
        if (!isset($this->routesByName[$lowercaseSearchName])) {
            throw new RouterNameNotFound($searchName);
        }

        // let's begin
        $url = $this->routesByName[$lowercaseSearchName];

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
            // Determine if we should skip parameter type checking
            $skipParameterTypeChecking = is_bool($skipParameterTypeChecking) ? $skipParameterTypeChecking : $this->skipParameterTypeChecking;
            // Get the URL matches
            foreach ($matches as $index => $match) {
                // convert to a string
                $value = (string)$arguments[$index];

                // make sure the argument matches the regular expression for that segment
                if (!$skipParameterTypeChecking && !preg_match('@' . $match[0] . '@m', $value)) {
                    throw new InvalidValue('Parameter mismatch. Expecting ' . $match[1] . ' got ' . $value);
                }

                // replace the segment with the passed argument
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
        // Determine the scheme
        if (is_string($prefix)) {
            // Use the custom prefix
            $scheme = $prefix;
        } else {
            // Auto determine the scheme based on the request
            $scheme = ($this->input->isHttpsRequest() ? 'https://' : 'http://');
        }

        // Build the site URL
        return $prefix ? $scheme . $this->siteUrl : $this->siteUrl;
    }

    /**
     * Saves the current routes to the cache if cache service provided.
     * This method serializes the routes and routesByName arrays
     *
     * @return void
     */
    protected function saveCache()
    {
        // Check if the cache is available and caching is not disabled
        if ($this->cache && !$this->disableCaching) {
            // Cache the current routes
            $this->cache->set($this->cacheKey, ['routes' => $this->routes, 'routesByName' => $this->routesByName]);
        }
    }

    /**
     * Loads routes from the cache or configuration.
     * If the cache is not available or empty, it will load routes from the configuration.
     * If the cache is available, it will check for cached routes and use them if valid.
     * If no cached routes are found, it will load the configuration routes and cache them.
     *
     * @return void
     * @throws MissingRequired
     */
    protected function loadRoutes(): void
    {
        // Check if the cache is available
        if ($this->cache) {
            // try to load the cached routes
            $cachedRoutes = $this->cache->get($this->cacheKey);

            // if we get "false" then it was a cache miss
            if (!$cachedRoutes) {
                // didn't find them so force a load and then set the cache
                $this->addConfigRoutes();
            } else {
                // cache is valid so we can use it
                $this->routes = $cachedRoutes['routes'];
                $this->routesByName = $cachedRoutes['routesByName'];
            }
        } else {
            // no cache being used so load the routes
            $this->addConfigRoutes();
        }
    }

    /**
     * Adds routes from the configuration.
     *
     * @throws MissingRequired If the configuration does not contain the required routes.
     *
     * @return void
     */
    protected function addConfigRoutes(): void
    {
        // turn off caching for addRoute(...)
        // addRoutes(...) will turn it back on and cache before exiting
        $this->disableCaching = true;

        // add 404 first which makes it the last in the search
        // add our default home - this could get overwritten by another home
        // add the user supplied routes
        $this->addRoute($this->config['404'])->addRoute($this->config['home'])->addRoutes($this->config['routes']);
    }
}
