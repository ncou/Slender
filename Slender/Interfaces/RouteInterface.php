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

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Route Interface
 */
interface RouteInterface
{

    /**
     * Retrieve a specific route argument
     */
    public function getArgument(string $name, ?string $default = null): ?string;

    /**
     * Get route arguments
     */
    public function getArguments(): array;

    /**
     * Get route name
     */
    public function getName(): ?string;

    /**
     * Get route pattern
     */
    public function getPattern(): string;

    /**
     * Set a route argument
     *
     * @return self
     */
    public function setArgument(string $name, string $value);

    /**
     * Replace route arguments
     *
     * @return self
     */
    public function setArguments(array $arguments);

    /**
     * Set route name
     *
     * @return static
    */
    public function setName(string $name);

    /**
     * Add middleware
     *
     * This method prepends new middleware to the route's middleware stack.
     *
     * @param callable|string $callable The callback routine
     *
     * @return RouteInterface
     */
    public function add($callable);

    /**
     * Prepare the route for use
     */
    public function prepare(ServerRequestInterface $request, array $arguments): void;

    /**
     * Run route
     *
     * This method traverses the middleware stack, including the route's callable
     * and captures the resultant HTTP response object. It then sends the response
     * back to the Application.
     */
    public function run(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;

    /**
     * Dispatch route callable against current Request and Response objects
     *
     * This method invokes the route object's callable. If middleware is
     * registered for the route, each callable middleware is invoked in
     * the order specified.
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}
