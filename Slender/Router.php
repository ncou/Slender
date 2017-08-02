<?php
declare(strict_types=1);

/**
 * Slender Framework (a derivative of the Slim Framework)
 * @link        https://github.com/RyanNerd/slender
 * @copyright   Copyright (c) 2017 Ryan Jentzsch
 * @license     https://github.com/RyanNerd/Slender/blob/master/LICENSE.md (MIT License)
 *
 * Slim Framework
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slender;

use FastRoute\Dispatcher;
use Psr\Container\ContainerInterface;
use InvalidArgumentException;
use RuntimeException;
use Psr\Http\Message\ServerRequestInterface;
use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use FastRoute\RouteParser\Std;
use Slender\Interfaces\RouterInterface;
use Slender\Interfaces\RouteInterface;
use function FastRoute\cachedDispatcher;
use function FastRoute\simpleDispatcher;

/**
 * Router
 *
 * This class organizes Slender application route objects. It is responsible
 * for registering route objects, assigning names to route objects,
 * finding routes that match the current HTTP request, and creating
 * URLs for a named route.
 */
class Router implements RouterInterface
{
    /**
     * Container Interface
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Parser
     *
     * @var RouteParser
     */
    protected $routeParser;

    /**
     * Base path used in pathFor()
     *
     * @var string
     */
    protected $basePath = '';

    /**
     * Path to fast route cache file. Set to null to disable
     *
     * @var string
     */
    protected $cacheFile = '';

    /**
     * Routes
     *
     * @var Route[]
     */
    protected $routes = [];

    /**
     * Route counter incrementer
     * @var int
     */
    protected $routeCounter = 0;

    /**
     * Route groups
     *
     * @var RouteGroup[]
     */
    protected $routeGroups = [];

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * Create new router
     */
    public function __construct()
    {
        $this->routeParser = new Std();
    }

    /**
     * Set the base path used in pathFor()
     */
    public function setBasePath(string $basePath): self
    {
        $this->basePath = $basePath;

        return $this;
    }

    /**
     * Set path to fast route cache file. If this is an empty string then route caching is disabled.
     */
    public function setCacheFile(string $cacheFile = ''): self
    {
        $this->cacheFile = $cacheFile;

        if ($cacheFile !== '' && !is_writable(dirname($cacheFile))) {
            throw new RuntimeException('Router cacheFile directory must be writable');
        }

        return $this;
    }

    public function setContainer(ContainerInterface $container): self
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Add route
     */
    public function map(array $methods, string $pattern, $handler): Route
    {
        if (!(is_string($handler) || is_callable($handler))) {
            throw new InvalidArgumentException();
        }
        // Prepend parent group pattern(s)
        if ($this->routeGroups) {
            $pattern = $this->processGroups() . $pattern;
        }

        // According to RFC methods are defined in uppercase (See RFC 7231)
        $methods = array_map("strtoupper", $methods);

        /**
         * Add route
         */
        $route = new Route($methods, $pattern, $handler, $this->routeGroups, $this->routeCounter);
        $this->routes[$route->getIdentifier()] = $route;
        $this->routeCounter++;

        return $route;
    }

    /**
     * Dispatch router for HTTP request
     * @link   https://github.com/nikic/FastRoute/blob/master/src/Dispatcher.php
     */
    public function dispatch(ServerRequestInterface $request): array
    {
        $uri = '/' . ltrim($request->getUri()->getPath(), '/');

        return $this->createDispatcher()->dispatch(
            $request->getMethod(),
            $uri
        );
    }

    protected function createDispatcher(): Dispatcher
    {
        if ($this->dispatcher) {
            return $this->dispatcher;
        }

        $routeDefinitionCallback = function (RouteCollector $r) {
            foreach ($this->getRoutes() as $route) {
                $r->addRoute($route->getMethods(), $route->getPattern(), $route->getIdentifier());
            }
        };

        if ($this->cacheFile !== '') {
            $this->dispatcher = cachedDispatcher($routeDefinitionCallback, [
                'routeParser' => $this->routeParser,
                'cacheFile' => ($this->cacheFile !== '' ? $this->cacheFile : false),
            ]);
        } else {
            $this->dispatcher = simpleDispatcher($routeDefinitionCallback, [
                'routeParser' => $this->routeParser,
            ]);
        }

        return $this->dispatcher;
    }

    /**
     * Only used for testing.
     */
    public function setDispatcher(Dispatcher $dispatcher): self
    {
        $this->dispatcher = $dispatcher;

        return $this;
    }

    /**
     * Get route objects
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Get named route object
     */
    public function getNamedRoute(string $name): RouteInterface
    {
        foreach ($this->routes as $route) {
            if ($name == $route->getName()) {
                return $route;
            }
        }
        throw new RuntimeException('Named route does not exist for name: ' . $name);
    }

    /**
     * Remove named route
     */
    public function removeNamedRoute(string $name): void
    {
        /** @var Route $route */
        $route = $this->getNamedRoute($name);

        // no exception, route exists, now remove by id
        unset($this->routes[$route->getIdentifier()]);
    }

    /**
     * Process route groups
     * Returns a group pattern to prefix routes with.
     */
    protected function processGroups(): string
    {
        $pattern = "";
        foreach ($this->routeGroups as $group) {
            $pattern .= $group->getPattern();
        }
        return $pattern;
    }

    /**
     * Add a route group to the array
     */
    public function pushGroup(string $pattern, $callable): RouteGroup
    {
        $group = new RouteGroup($pattern, $callable);
        array_push($this->routeGroups, $group);
        return $group;
    }

    /**
     * Removes the last route group from the array
     */
    public function popGroup(): ?RouteGroup
    {
        $group = array_pop($this->routeGroups);
        return $group instanceof RouteGroup ? $group : null;
    }

    public function lookupRoute($identifier): RouteInterface
    {
        if (!isset($this->routes[$identifier])) {
            throw new RuntimeException('Route not found, looks like your route cache is stale.');
        }
        return $this->routes[$identifier];
    }

    /**
     * Build the path for a named route excluding the base path
     */
    public function relativePathFor(string $name, array $data = [], array $queryParams = []): string
    {
        $route = $this->getNamedRoute($name);
        $pattern = $route->getPattern();

        $routeDatas = $this->routeParser->parse($pattern);
        // $routeDatas is an array of all possible routes that can be made. There is
        // one routedata for each optional parameter plus one for no optional parameters.
        //
        // The most specific is last, so we look for that first.
        $routeDatas = array_reverse($routeDatas);

        $segmentName = null;
        $segments = [];
        foreach ($routeDatas as $routeData) {
            foreach ($routeData as $item) {
                if (is_string($item)) {
                    // this segment is a static string
                    $segments[] = $item;
                    continue;
                }

                // This segment has a parameter: first element is the name
                if (!array_key_exists($item[0], $data)) {
                    // we don't have a data element for this segment: cancel
                    // testing this routeData item, so that we can try a less
                    // specific routeData item.
                    $segments = [];
                    $segmentName = $item[0];
                    break;
                }
                $segments[] = $data[$item[0]];
            }
            if (!empty($segments)) {
                // we found all the parameters for this route data, no need to check
                // less specific ones
                break;
            }
        }

        if (empty($segments)) {
            throw new InvalidArgumentException('Missing data for URL segment: ' . $segmentName);
        }
        $url = implode('', $segments);

        if ($queryParams) {
            $url .= '?' . http_build_query($queryParams);
        }

        return $url;
    }


    /**
     * Build the path for a named route including the base path
     */
    public function pathFor(string $name, array $data = [], array $queryParams = []): string
    {
        $url = $this->relativePathFor($name, $data, $queryParams);

        if ($this->basePath) {
            $url = $this->basePath . $url;
        }

        return $url;
    }
}
