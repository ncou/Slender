<?php
declare(strict_types=1);

/**
 * Slender is a fork of the Slim Framework with these primary differences, similarities and enhancements:
 * - Slender implements PHP 7.1 features where it makes sense to do so
 *   (scalar type declarations, scoped constants, spaceship operator, null coalesce operator, etc.).
 * - Slender removes the pimple Dependency Injection (DI) dependency from composer in favor of PHP-DI.
 * - PHP-DI is "baked in" meaning that the code from PHP-DI/Slim-Bridge (1.0.3)
 *   https://github.com/PHP-DI/Slim-Bridge is HARD CODED into the app.
 *   This is necessary because Slim-Bridge extends Slim and (of course) not Slender.
 * - Slender tries (but does not guarantee) to keep up with changes to the Slim 3.x branch.
 * - Work is starting on Slim 4.x once this becomes a stable release Slender will try to "catch up"
 *
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

use DI\Container;
use DI\ContainerBuilder;
use Exception;
use Slender\Http\Response;
use Throwable;
use Closure;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;
use FastRoute\Dispatcher;
use Slender\Exception\MethodNotAllowedException;
use Slender\Exception\NotFoundException;
use Slender\Http\Uri;
use Slender\Http\Headers;
use Slender\Http\Body;
use Slender\Http\Request;
use Slender\Interfaces\RouteGroupInterface;
use Slender\Interfaces\RouteInterface;
use Slender\Interfaces\RouterInterface;
use Slender\Utility\Library;

/**
 * App
 *
 * This is the primary class with which you instantiate,
 * configure, and run a Slender Framework application.
 * The \Slender\App class also accepts Slender Framework middleware.
 *
 * @property-read callable $errorHandler
 * @property-read callable $phpErrorHandler
 * @property-read callable $notFoundHandler function($request, $response)
 * @property-read callable $notAllowedHandler function($request, $response, $allowedHttpMethods)
 */
class App
{
    use MiddlewareAwareTrait;

    /**
     * Current version
     *
     * @var string
     */
    public const VERSION = '1.0.0-dev';

    /**
     * Container
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var  Request
     */
    protected $request;

    /**
     * @var  Response
     */
    protected $response;

    /**
     * @var array
     */
    protected $settings;

    /********************************************************************************
     * Constructor
     *******************************************************************************/

    /**
     * Create new application
     */
    public function __construct(array $definitions = [])
    {
        assert(Library::valid_num_args());

        $containerBuilder = new ContainerBuilder();

        // If we have an empty definitions array then set the definitions to read from the default config file instead.
        if (count($definitions) === 0) {
            $definitions = __DIR__ . '/config.php';
        }

        $containerBuilder->useAutowiring(true);
        $containerBuilder->addDefinitions($definitions);
        $this->configureContainer($containerBuilder);
        $container = $containerBuilder->build();

        $this->router = $container->get('router');
        $this->request = $container->get('request');
        $this->response = $container->get('response');
        $this->settings = $container->get('settings');
        $this->container = $container;
    }

    /**
     * Override this method to configure the container builder.
     *
     * For example, to load additional configuration files:
     *
     *     protected function configureContainer(ContainerBuilder $builder)
     *     {
     *         $builder->addDefinitions(__DIR__ . 'my-config-file.php');
     *     }
     */
    protected function configureContainer(ContainerBuilder $builder)
    {
    }

    /**
     * Enable access to the DI container by consumers of $app
     */
    public function getContainer(): Container
    {
        assert(Library::valid_num_args());

        return $this->container;
    }

    /**
     * Add middleware
     *
     * This method prepends new middleware to the app's middleware stack.
     */
    public function add($callable): self
    {
        assert(Library::valid_num_args());

        return $this->addMiddleware(new DeferredCallable($callable, $this->container));
    }

    /**
     * Calling a non-existant method on App checks to see if there's an item
     * in the container that is callable and if so, calls it.
     */
    public function __call($method, $args)
    {
        if ($this->container->has($method)) {
            $obj = $this->container->get($method);
            if (is_callable($obj)) {
                return $obj(...$args);
            }
        }

        throw new \BadMethodCallException("Method $method is not a valid method");
    }

    /********************************************************************************
     * Router proxy methods
     *******************************************************************************/

    /**
     * Add GET route
     */
    public function get(string $pattern, $callable): RouteInterface
    {
        assert(Library::valid_num_args());

        return $this->map(['GET'], $pattern, $callable);
    }

    /**
     * Add POST route
     */
    public function post(string $pattern, $callable): RouteInterface
    {
        assert(Library::valid_num_args());

        return $this->map(['POST'], $pattern, $callable);
    }

    /**
     * Add PUT route
     */
    public function put(string $pattern, $callable): RouteInterface
    {
        assert(Library::valid_num_args());

        return $this->map(['PUT'], $pattern, $callable);
    }

    /**
     * Add PATCH route
     */
    public function patch(string $pattern, $callable): RouteInterface
    {
        assert(Library::valid_num_args());

        return $this->map(['PATCH'], $pattern, $callable);
    }

    /**
     * Add DELETE route
     */
    public function delete(string $pattern, $callable): RouteInterface
    {
        assert(Library::valid_num_args());

        return $this->map(['DELETE'], $pattern, $callable);
    }

    /**
     * Add OPTIONS route
     */
    public function options(string $pattern, $callable): RouteInterface
    {
        assert(Library::valid_num_args());

        return $this->map(['OPTIONS'], $pattern, $callable);
    }

    /**
     * Add route for any HTTP method
     */
    public function any(string $pattern, $callable): RouteInterface
    {
        assert(Library::valid_num_args());

        return $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $callable);
    }

    /**
     * Add route with multiple methods
     */
    public function map(array $methods, string $pattern, $callable): RouteInterface
    {
        assert(Library::valid_num_args());

        if ($callable instanceof Closure) {
            $callable = $callable->bindTo($this->container);
        }

        /** @var Router $route */
        $route = $this->router->map($methods, $pattern, $callable);
        if (is_callable([$route, 'setContainer'])) {
            $route->setContainer($this->container);
        }

        /** @var Route $route */
        if (is_callable([$route, 'setOutputBuffering'])) {
            $route->setOutputBuffering((string)$this->settings['outputBuffering']);
        }

        return $route;
    }

    /**
     * Route Groups
     *
     * This method accepts a route pattern and a callback. All route
     * declarations in the callback will be prepended by the group(s)
     * that it is in.
     */
    public function group(string $pattern, $callable): RouteGroupInterface
    {
        assert(Library::valid_num_args());

        $group = $this->router->pushGroup($pattern, $callable);
        $group->setContainer($this->container);
        $group($this);
        $this->router->popGroup();
        return $group;
    }

    /********************************************************************************
     * Runner
     *******************************************************************************/

    /**
     * Run application
     *
     * This method traverses the application middleware stack and then sends the
     * resultant Response object to the HTTP client.
     */
    public function run(bool $silent = false): ResponseInterface
    {
        assert(Library::valid_num_args());

        // Error and Exception handling is now a part of middleware (not possible with pre PHP 7).
        $this->add([$this, 'handleThrowable']);

        // Process the request
        $response = $this->process($this->request, $this->response);

        if (!$silent) {
            $this->respond($response);
        }

        return $response;
    }

    /**
     * This method is added as a part of the middleware of Slender for better control over error and exception handling
     * Since this is Last In First Executed (LIFE) it is executed first wrapping all processing in a try...catch
     */
    public function handleThrowable(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    ): ResponseInterface {
        assert(Library::valid_num_args());

        try {
            return $next($request, $response);
        } catch (\Throwable $throwable) {
            return $this->processException($throwable, $request, $response);
        }
    }

    /**
     * Process a request
     *
     * This method traverses the application middleware stack and then returns the
     * resultant Response object.
     */
    public function process(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        assert(Library::valid_num_args());

        /**
         * Ensure basePath is set
         * @var Request $request
         */
        $router = $this->router;
        $router->setBasePath($request->getUri()->getBasePath());

        // Dispatch the Router first if the setting for this is on
        if ($this->settings['determineRouteBeforeAppMiddleware'] === true) {
            // Dispatch router (note: you won't be able to alter routes after this)
            $request = $this->dispatchRouterAndPrepareRoute($request, $router);
        }

        $response = $this->callMiddlewareStack($request, $response);
        $response = $this->finalize($response);
        return $response;
    }

    protected function processException(\Throwable $e, ServerRequestInterface $request, ResponseInterface $response)
    {
        assert(Library::valid_num_args());

        switch ($e) {
            case ($e instanceof MethodNotAllowedException):
                $handler = 'notAllowedHandler';
                if ($this->container->has($handler)) {
                    $callable = $this->container->get($handler);
                    return $callable($e->getRequest(), $e->getResponse(), $e->getAllowedMethods());
                }
                throw $e;

            case ($e instanceof NotFoundException):
                $handler = 'notFoundHandler';
                if ($this->container->has($handler)) {
                    $callable = $this->container->get($handler);
                    return $callable($e->getRequest(), $e->getResponse(), $e);
                }
                throw $e;

            default:
                $handler = 'errorHandler';
                if ($this->container->has($handler)) {
                    $callable = $this->container->get($handler);
                    return $callable($request, $response, $e);
                }
                throw $e;
        }
    }

    /**
     * Send the response the client
     */
    public function respond(ResponseInterface $response): void
    {
        assert(Library::valid_num_args());

        // Send response
        if (!headers_sent()) {
            // Status
            header(sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));

            // Headers
            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }

        // Body
        if (!$this->isEmptyResponse($response)) {
            $body = $response->getBody();
            if ($body->isSeekable()) {
                $body->rewind();
            }
            $settings       = $this->settings;
            $chunkSize      = (int)$settings['responseChunkSize'];

            $contentLength  = $response->getHeaderLine('Content-Length');
            if (!$contentLength) {
                $contentLength = $body->getSize();
            }


            if (isset($contentLength)) {
                $amountToRead = $contentLength;
                while ($amountToRead > 0 && !$body->eof()) {
                    $data = $body->read(min($chunkSize, $amountToRead));
                    echo $data;

                    $amountToRead -= strlen($data);

                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            } else {
                while (!$body->eof()) {
                    echo $body->read($chunkSize);
                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            }
        }
    }

    /**
     * Invoke application
     *
     * This method implements the middleware interface. It receives
     * Request and Response objects, and it returns a Response object
     * after compiling the routes registered in the Router and dispatching
     * the Request object to the appropriate Route callback routine.
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        assert(Library::valid_num_args());

        // Get the route info
        $routeInfo = $request->getAttribute('routeInfo');

        /** @var \Slender\Interfaces\RouterInterface $router */
        $router = $this->router;

        // If router hasn't been dispatched or the URI changed then dispatch
        if (null === $routeInfo || ($routeInfo['request'] !== [$request->getMethod(), (string) $request->getUri()])) {
            $request = $this->dispatchRouterAndPrepareRoute($request, $router);
            $routeInfo = $request->getAttribute('routeInfo');
        }

        if ($routeInfo[RouterInterface::DISPATCH_STATUS] === Dispatcher::FOUND) {
            $route = $router->lookupRoute($routeInfo[RouterInterface::ALLOWED_METHODS]);
            return $route->run($request, $response);
        } elseif ($routeInfo[RouterInterface::DISPATCH_STATUS] === Dispatcher::METHOD_NOT_ALLOWED) {
            if (!$this->container->has('notAllowedHandler')) {
                throw new MethodNotAllowedException($request, $response, $routeInfo[RouterInterface::ALLOWED_METHODS]);
            }
            /** @var callable $notAllowedHandler */
            $notAllowedHandler = $this->container->get('notAllowedHandler');
            return $notAllowedHandler($request, $response, $routeInfo[RouterInterface::ALLOWED_METHODS]);
        }

        if (!$this->container->has('notFoundHandler')) {
            throw new NotFoundException($request, $response);
        }
        /** @var callable $notFoundHandler */
        $notFoundHandler = $this->container->get('notFoundHandler');
        return $notFoundHandler($request, $response);
    }

    /**
     * Perform a sub-request from within an application route
     *
     * This method allows you to prepare and initiate a sub-request, run within
     * the context of the current request. This WILL NOT issue a remote HTTP
     * request. Instead, it will route the provided URL, method, headers,
     * cookies, body, and server variables against the set of registered
     * application routes. The result response object is returned.
     *
     * @param  string            $method      The request method (e.g., GET, POST, PUT, etc.)
     * @param  string            $path        The request URI path
     * @param  string            $query       The request URI query string
     * @param  array             $headers     The request headers (key-value array)
     * @param  array             $cookies     The request cookies (key-value array)
     * @param  string            $bodyContent The request body
     * @param  ResponseInterface $response     The response object (optional)
     * @return ResponseInterface
     */
    public function subRequest(
        string $method,
        string $path,
        string $query = '',
        array $headers = [],
        array $cookies = [],
        string $bodyContent = '',
        ResponseInterface $response = null
    ): ResponseInterface {
        assert(Library::valid_num_args());

        $env = $this->container->get('environment');
        $uri = Uri::createFromEnvironment($env)->withPath($path)->withQuery($query);
        $headers = new Headers($headers);
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($bodyContent);
        $body->rewind();
        $request = new Request($method, $uri, $headers, $cookies, $serverParams, $body);

        if (!$response) {
            $response = $this->response;
        }

        return $this($request, $response);
    }

    /**
     * Dispatch the router to find the route. Prepare the route for use.
     */
    protected function dispatchRouterAndPrepareRoute(
        ServerRequestInterface $request,
        RouterInterface $router
    ): ServerRequestInterface {
        assert(Library::valid_num_args());

        $routeInfo = $router->dispatch($request);

        if ($routeInfo[RouterInterface::DISPATCH_STATUS] === Dispatcher::FOUND) {
            $routeArguments = [];
            foreach ($routeInfo[RouterInterface::ROUTE_ARGUMENTS] as $k => $v) {
                $routeArguments[$k] = urldecode($v);
            }

            $route = $router->lookupRoute($routeInfo[RouterInterface::ALLOWED_METHODS]);
            $route->prepare($request, $routeArguments);

            // add route to the request's attributes in case a middleware or handler needs access to the route
            $request = $request->withAttribute('route', $route);
        }

        $routeInfo['request'] = [$request->getMethod(), (string)$request->getUri()];

        return $request->withAttribute('routeInfo', $routeInfo);
    }

    /**
     * Finalize response
     */
    protected function finalize(ResponseInterface $response): ResponseInterface
    {
        assert(Library::valid_num_args());

        // stop PHP sending a Content-Type automatically
        ini_set('default_mimetype', '');

        if ($this->isEmptyResponse($response)) {
            return $response->withoutHeader('Content-Type')->withoutHeader('Content-Length');
        }

        // Add Content-Length header if `addContentLengthHeader` setting is set
        if (isset($this->settings['addContentLengthHeader']) &&
            $this->settings['addContentLengthHeader'] == true) {
            if (ob_get_length() > 0) {
                throw new \RuntimeException("Unexpected data in output buffer. " .
                    "Maybe you have characters before an opening <?php tag?");
            }
            $size = $response->getBody()->getSize();
            if ($size !== null && !$response->hasHeader('Content-Length')) {
                $response = $response->withHeader('Content-Length', (string) $size);
            }
        }

        return $response;
    }

    /**
     * Helper method, which returns true if the provided response must not output a body and false
     * if the response could have a body.
     *
     * @see https://tools.ietf.org/html/rfc7231
     */
    protected function isEmptyResponse(ResponseInterface $response): bool
    {
        assert(Library::valid_num_args());

        if (method_exists($response, 'isEmpty')) {
            return $response->isEmpty();
        }

        return in_array($response->getStatusCode(), [204, 205, 304]);
    }
}
