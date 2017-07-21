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
// @codingStandardsIgnoreStart
use DI\Container;
use function DI\get;
use function DI\object;
use Interop\Container\ContainerInterface;
use Invoker\
{
    Invoker,
    ParameterResolver\AssociativeArrayResolver,
    ParameterResolver\Container\TypeHintContainerResolver,
    ParameterResolver\DefaultValueResolver,
    ParameterResolver\ResolverChain
};

use Slender\{
    CallableResolver,
    ControllerInvoker,
    Http\Headers,
    Http\Request,
    Http\Response
};
// @codingStandardsIgnoreEnd

return [

    // Settings that can be customized by users
    'settings.httpVersion' => '1.1',
    'settings.responseChunkSize' => 4096,
    'settings.outputBuffering' => 'append',
    'settings.determineRouteBeforeAppMiddleware' => false,
    'settings.displayErrorDetails' => false,
    'settings.addContentLengthHeader' => true,
    'settings.routerCacheFile' => false,

    'settings' => [
        'httpVersion' => get('settings.httpVersion'),
        'responseChunkSize' => get('settings.responseChunkSize'),
        'outputBuffering' => get('settings.outputBuffering'),
        'determineRouteBeforeAppMiddleware' => get('settings.determineRouteBeforeAppMiddleware'),
        'displayErrorDetails' => get('settings.displayErrorDetails'),
        'addContentLengthHeader' => get('settings.addContentLengthHeader'),
        'routerCacheFile' => get('settings.routerCacheFile'),
    ],

    // Default services
    'router' => object(Slender\Router::class)
        ->method('setCacheFile', get('settings.routerCacheFile')),
    Slender\Router::class => get('router'),
    'errorHandler' => object(Slender\Handlers\Error::class)
        ->constructor(get('settings.displayErrorDetails')),
    'phpErrorHandler' => object(Slender\Handlers\PhpError::class)
        ->constructor(get('settings.displayErrorDetails')),
    'notFoundHandler' => object(Slender\Handlers\NotFound::class),
    'notAllowedHandler' => object(Slender\Handlers\NotAllowed::class),
    'environment' => function () {
        return new Slender\Http\Environment($_SERVER);
    },
    'request' => function (ContainerInterface $c) {
        return Request::createFromEnvironment($c->get('environment'));
    },
    'response' => function (ContainerInterface $c) {
        $headers = new Headers(['Content-Type' => 'text/html; charset=UTF-8']);
        $response = new Response(200, $headers);
        return $response->withProtocolVersion($c->get('settings')['httpVersion']);
    },
    'foundHandler' => object(ControllerInvoker::class)
        ->constructor(get('foundHandler.invoker')),
    'foundHandler.invoker' => function (ContainerInterface $c) {
        $resolvers = [
            // Inject parameters by name first
            new AssociativeArrayResolver,
            // Then inject services by type-hints for those that weren't resolved
            new TypeHintContainerResolver($c),
            // Then fall back on parameters default values for optional route parameters
            new DefaultValueResolver(),
        ];
        return new Invoker(new ResolverChain($resolvers), $c);
    },

    'callableResolver' => object(CallableResolver::class),

    // Aliases
    ContainerInterface::class => get(Container::class)
];
