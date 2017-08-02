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

use Exception;
use Throwable;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slender\Handlers\Strategies\RequestResponse;
use Slender\Interfaces\InvocationStrategyInterface;
use Slender\Interfaces\RouteInterface;

/**
 * Route
 */
class Route extends Routable implements RouteInterface
{
    use MiddlewareAwareTrait;

    /**
     * HTTP methods supported by this route
     *
     * @var string[]
     */
    protected $methods = [];

    /**
     * Route identifier
     *
     * @var string
     */
    protected $identifier;

    /**
     * Route name
     *
     * @var null|string
     */
    protected $name;

    /**
     * Parent route groups
     *
     * @var RouteGroup[]
     */
    protected $groups;

    private $finalized = false;

    /**
     * Output buffering mode
     *
     * One of: 'none', 'prepend' or 'append'
     *
     * @var string
     */
    protected $outputBuffering = 'append';

    /**
     * Route parameters
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The callable payload
     *
     * @var callable
     */
    protected $callable;

    /**
     * Create new route
     *
     * @param string|string[]   $methods The route HTTP methods
     * @param string            $pattern The route pattern
     * @param callable          $callable The route callable
     * @param RouteGroup[]      $groups The parent route groups
     * @param int               $identifier The route identifier
     */
    public function __construct(array $methods, string $pattern, $callable, array $groups = [], int $identifier = 0)
    {
        if (!(is_string($callable) || is_callable($callable))) {
            throw new InvalidArgumentException();
        }

        $this->methods  = is_string($methods) ? [$methods] : $methods;
        $this->pattern  = $pattern;
        $this->callable = $callable;
        $this->groups   = $groups;
        $this->identifier = 'route' . $identifier;
    }

    /**
     * Finalize the route in preparation for dispatching
     */
    public function finalize(): void
    {
        if ($this->finalized) {
            return;
        }

        $groupMiddleware = [];
        foreach ($this->getGroups() as $group) {
            $groupMiddleware = array_merge($group->getMiddleware(), $groupMiddleware);
        }

        $this->middleware = array_merge($this->middleware, $groupMiddleware);

        foreach ($this->getMiddleware() as $middleware) {
            $this->addMiddleware($middleware);
        }

        $this->finalized = true;
    }

    /**
     * Get route callable
     *
     * @return string|\Closure
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * This method enables you to override the Route's callable
     *
     * @param string|\Closure $callable
     */
    public function setCallable($callable): void
    {
        if (!(is_string($callable) || is_callable($callable))) {
            throw new InvalidArgumentException();
        }

        $this->callable = $callable;
    }

    /**
     * Get route methods
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Get parent route groups
     *
     * @return RouteGroup[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * Get route name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get route identifier
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Get output buffering mode
     */
    public function getOutputBuffering(): string
    {
        return $this->outputBuffering;
    }

    /**
     * Set output buffering mode
     *
     * One of: 'none', 'prepend' or 'append'
     */
    public function setOutputBuffering(string $mode): self
    {
        if (!in_array($mode, ['none', 'prepend', 'append'], true)) {
            throw new InvalidArgumentException('Unknown output buffering mode');
        }

        $this->outputBuffering = $mode;
        return $this;
    }

    /**
     * Set route name
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set a route argument
     */
    public function setArgument(string $name, string $value): self
    {
        $this->arguments[$name] = $value;
        return $this;
    }

    /**
     * Replace route arguments
     */
    public function setArguments(array $arguments): self
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * Retrieve route arguments
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Retrieve a specific route argument
     */
    public function getArgument(string $name, ?string $default = null): ?string
    {
        if (array_key_exists($name, $this->arguments)) {
            return $this->arguments[$name];
        }
        return $default;
    }

    /********************************************************************************
     * Route Runner
     *******************************************************************************/

    /**
     * Prepare the route for use
     */
    public function prepare(ServerRequestInterface $request, array $arguments): void
    {
        // Add the arguments
        foreach ($arguments as $k => $v) {
            $this->setArgument($k, $v);
        }
    }

    /**
     * Run route
     *
     * This method traverses the middleware stack, including the route's callable
     * and captures the resultant HTTP response object. It then sends the response
     * back to the Application.
     */
    public function run(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Finalise route now that we are about to run it
        $this->finalize();

        // Traverse middleware stack and fetch updated response
        return $this->callMiddlewareStack($request, $response);
    }

    /**
     * Dispatch route callable against current Request and Response objects
     *
     * This method invokes the route object's callable. If middleware is
     * registered for the route, each callable middleware is invoked in
     * the order specified.
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->callable = $this->resolveCallable($this->callable);

        /** @var InvocationStrategyInterface $handler */
        $handler = isset($this->container) ? $this->container->get('foundHandler') : new RequestResponse();

        // invoke route callable
        if ($this->outputBuffering === 'none') {
            $newResponse = $handler($this->callable, $request, $response, $this->arguments);
        } else {
            try {
                ob_start();
                $newResponse = $handler($this->callable, $request, $response, $this->arguments);
                $output = ob_get_clean();
            } catch (Throwable $e) {
                ob_end_clean();
                throw $e;
            }
        }

        if ($newResponse instanceof ResponseInterface) {
            // if route callback returns a ResponseInterface, then use it
            $response = $newResponse;
        } elseif (is_string($newResponse)) {
            // if route callback returns a string, then append it to the response
            if ($response->getBody()->isWritable()) {
                $response->getBody()->write($newResponse);
            }
        }

        if (!empty($output) && $response->getBody()->isWritable()) {
            if ($this->outputBuffering === 'prepend') {
                // prepend output buffer content
                $body = new Http\Body(fopen('php://temp', 'r+'));
                $body->write($output . $response->getBody());
                $response = $response->withBody($body);
            } elseif ($this->outputBuffering === 'append') {
                // append output buffer content
                $response->getBody()->write($output);
            }
        }

        return $response;
    }
}
