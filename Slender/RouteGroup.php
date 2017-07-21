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

use Closure;
use Slender\Interfaces\RouteGroupInterface;

/**
 * A collector for Routable objects with a common middleware stack
 */
class RouteGroup extends Routable implements RouteGroupInterface
{
    /**
     * Create a new RouteGroup
     */
    public function __construct(string $pattern, $callable)
    {
        if (!(is_string($callable) || is_callable($callable))) {
            throw new \InvalidArgumentException();
        }

        $this->pattern = $pattern;
        $this->callable = $callable;
    }

    /**
     * Invoke the group to register any Routable objects within it.
     */
    public function __invoke(App $app = null): void
    {
        $callable = $this->resolveCallable($this->callable);
        if ($callable instanceof Closure && $app !== null) {
            $callable = $callable->bindTo($app);
        }

        $callable();
    }
}
