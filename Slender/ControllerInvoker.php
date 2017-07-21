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

use Invoker\InvokerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slender\Interfaces\InvocationStrategyInterface;

class ControllerInvoker implements InvocationStrategyInterface
{
    /**
     * @var InvokerInterface
     */
    private $invoker;

    public function __construct(InvokerInterface $invoker)
    {
        $this->invoker = $invoker;
    }

    /**
     * Invoke a route callable.
     *
     * @return ResponseInterface|string The response from the callable.
     */
    public function __invoke(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    ) {
        // Inject the request and response by parameter name
        $parameters = [
            'request'  => $request,
            'response' => $response,
        ];

        // Inject the route arguments by name
        $parameters += $routeArguments;

        return $this->invoker->call($callable, $parameters);
    }
}
