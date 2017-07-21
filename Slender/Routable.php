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

use Psr\Container\ContainerInterface;

/**
 * A routable, middleware-aware object
 */
abstract class Routable
{
    use CallableResolverAwareTrait;

    /**
     * Route callable
     *
     * @var callable
     */
    protected $callable;

    /**
     * Container
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Route middleware
     *
     * @var callable[]
     */
    protected $middleware = [];

    /**
     * Route pattern
     *
     * @var string
     */
    protected $pattern;

    /**
     * Get the middleware registered for the group
     *
     * @return callable[]
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Get the route pattern
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * Set container for use with resolveCallable
     */
    public function setContainer(ContainerInterface $container): self
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Prepend middleware to the middleware collection
     */
    public function add($callable): self
    {
        $this->middleware[] = new DeferredCallable($callable, $this->container);
        return $this;
    }

    /**
     * Set the route pattern
     */
    public function setPattern(string $newPattern): void
    {
        $this->pattern = $newPattern;
    }
}
