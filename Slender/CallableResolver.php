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
 *
 * PHP-DI/Slim-Bridge is "baked-in"
 * Copied from: https://github.com/PHP-DI/Slim-Bridge
 * @license https://github.com/PHP-DI/Slim-Bridge/blob/master/LICENSE
 */
namespace Slender;

use Slender\Interfaces\CallableResolverInterface;

/**
 * Resolve middleware and route callables using PHP-DI.
 */
class CallableResolver implements CallableResolverInterface
{
    /**
     * @var \Invoker\CallableResolver
     */
    private $callableResolver;

    public function __construct(\Invoker\CallableResolver $callableResolver)
    {
        $this->callableResolver = $callableResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($toResolve): callable
    {
        return $this->callableResolver->resolve($toResolve);
    }
}
