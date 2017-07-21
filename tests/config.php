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
use Slender\CallableResolver;
use Slender\ControllerInvoker;
use DI\Container;
use Interop\Container\ContainerInterface;
use Invoker\Invoker;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\ResolverChain;
use Slender\Http\Headers;
use Slender\Http\Request;
use Slender\Http\Response;
use Slender\Http\Environment;

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
        'httpVersion' => DI\get('settings.httpVersion'),
        'responseChunkSize' => DI\get('settings.responseChunkSize'),
        'outputBuffering' => DI\get('settings.outputBuffering'),
        'determineRouteBeforeAppMiddleware' => DI\get('settings.determineRouteBeforeAppMiddleware'),
        'displayErrorDetails' => DI\get('settings.displayErrorDetails'),
        'addContentLengthHeader' => DI\get('settings.addContentLengthHeader'),
        'routerCacheFile' => DI\get('settings.routerCacheFile'),
    ],

    // Default services
    'router' => DI\object(Slender\Router::class)
        ->method('setCacheFile', DI\get('settings.routerCacheFile')),
    Slender\Router::class => DI\get('router'),
    'errorHandler' => DI\object(Slender\Handlers\Error::class)
        ->constructor(DI\get('settings.displayErrorDetails')),
    'phpErrorHandler' => DI\object(Slender\Handlers\PhpError::class)
        ->constructor(DI\get('settings.displayErrorDetails')),
    'notFoundHandler' => DI\object(Slender\Handlers\NotFound::class),
    'notAllowedHandler' => DI\object(Slender\Handlers\NotAllowed::class),
    'environment' => function () {
        return Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET'
        ]);
    },
    'request' => function (ContainerInterface $c) {
        return Request::createFromEnvironment($c->get('environment'));
    },
    'response' => function (ContainerInterface $c) {
        $headers = new Headers(['Content-Type' => 'text/html; charset=UTF-8']);
        $response = new Response(200, $headers);
        return $response->withProtocolVersion($c->get('settings')['httpVersion']);
    },
    'foundHandler' => DI\object(ControllerInvoker::class)
        ->constructor(DI\get('foundHandler.invoker')),
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

    'callableResolver' => DI\object(CallableResolver::class),

    // Aliases
    ContainerInterface::class => DI\get(Container::class)
];
