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
namespace Slender\Interfaces;

use RuntimeException;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Slender\Route;
use Slender\RouteGroup;

/**
 * Router Interface
 */
interface RouterInterface
{
    // array keys from route result
    public const DISPATCH_STATUS = 0;
    public const ALLOWED_METHODS = 1;
    public const ROUTE_ARGUMENTS = 2;

    /**
     * Add route
     *
     * @param string[] $methods Array of HTTP methods
     * @param string   $pattern The route pattern
     * @param callable $handler The route callable
     *
     * @return RouteInterface
     */
    public function map(array $methods, string $pattern, $handler): Route;

    /**
     * Dispatch router for HTTP request
     * @link   https://github.com/nikic/FastRoute/blob/master/src/Dispatcher.php
     */
    public function dispatch(ServerRequestInterface $request): array;

    /**
     * Add a route group to the array
     */
    public function pushGroup(string $pattern, $callable): RouteGroup;

    /**
     * Removes the last route group from the array
     *
     * @return null|RouteGroup removed
     */
    public function popGroup();

    /**
     * Get named route object
     */
    public function getNamedRoute(string $name): RouteInterface;

    /**
     * @param $identifier
     *
     * @return \Slender\Interfaces\RouteInterface
     */
    public function lookupRoute($identifier): RouteInterface;

    /**
     * Build the path for a named route excluding the base path
     *
     * @param string $name        Route name
     * @param array  $data        Named argument replacement data
     * @param array  $queryParams Optional query string parameters
     *
     * @return string
     *
     * @throws RuntimeException         If named route does not exist
     * @throws InvalidArgumentException If required data not provided
     */
    public function relativePathFor(string $name, array $data = [], array $queryParams = []): string;

    /**
     * Build the path for a named route including the base path
     *
     * @param string $name        Route name
     * @param array  $data        Named argument replacement data
     * @param array  $queryParams Optional query string parameters
     *
     * @return string
     *
     * @throws RuntimeException         If named route does not exist
     * @throws InvalidArgumentException If required data not provided
     */
    public function pathFor(string $name, array $data = [], array $queryParams = []): string;
}
