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
namespace Slender\Tests;

use DI\ContainerBuilder;
use function DI\object;
use Slender\DeferredCallable;
use Slender\Http\Body;
use Slender\Http\Environment;
use Slender\Http\Headers;
use Slender\Http\Request;
use Slender\Http\Response;
use Slender\Http\Uri;
use Slender\Route;
use Slender\Tests\Mocks\CallableTest;
use Slender\Tests\Mocks\InvocationStrategyTest;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    public function routeFactory()
    {
        $methods = ['GET', 'POST'];
        $pattern = '/hello/{name}';
        $callable = function ($req, $res, $args) {
            // Do something
        };

        return new Route($methods, $pattern, $callable);
    }

    public function testConstructor()
    {
        $methods = ['GET', 'POST'];
        $pattern = '/hello/{name}';
        $callable = function ($req, $res, $args) {
            // Do something
        };
        $route = new Route($methods, $pattern, $callable);

        $this->assertAttributeEquals($methods, 'methods', $route);
        $this->assertAttributeEquals($pattern, 'pattern', $route);
        $this->assertAttributeEquals($callable, 'callable', $route);
    }

    public function testGetMethodsReturnsArrayWhenContructedWithString()
    {
        $route = new Route(['GET'], '/hello', function ($req, $res, $args) {
            // Do something
        });

        $this->assertEquals(['GET'], $route->getMethods());
    }

    public function testGetMethods()
    {
        $this->assertEquals(['GET', 'POST'], $this->routeFactory()->getMethods());
    }

    public function testGetPattern()
    {
        $this->assertEquals('/hello/{name}', $this->routeFactory()->getPattern());
    }

    public function testGetCallable()
    {
        $callable = $this->routeFactory()->getCallable();

        $this->assertTrue(is_callable($callable));
    }

    public function testArgumentSetting()
    {
        $route = $this->routeFactory();
        $route->setArguments(['foo' => 'FOO', 'bar' => 'BAR']);
        $this->assertSame($route->getArguments(), ['foo' => 'FOO', 'bar' => 'BAR']);
        $route->setArgument('bar', 'bar');
        $this->assertSame($route->getArguments(), ['foo' => 'FOO', 'bar' => 'bar']);
        $route->setArgument('baz', 'BAZ');
        $this->assertSame($route->getArguments(), ['foo' => 'FOO', 'bar' => 'bar', 'baz' => 'BAZ']);

        $route->setArguments(['a' => 'b']);
        $this->assertSame($route->getArguments(), ['a' => 'b']);
        $this->assertSame($route->getArgument('a', 'default'), 'b');
        $this->assertSame($route->getArgument('b', 'default'), 'default');
    }


    public function testBottomMiddlewareIsRoute()
    {
        $route = $this->routeFactory();
        $mw = function ($req, $res, $next) {
            return $res;
        };
        $route->add($mw);
        $route->finalize();

        $prop = new \ReflectionProperty($route, 'stack');
        $prop->setAccessible(true);

        $this->assertEquals($route, $prop->getValue($route)->bottom());
    }

    public function testAddMiddleware()
    {
        $route = $this->routeFactory();
        $mw = function ($req, $res, $next) {
            return $res;
        };
        $route->add($mw);
        $route->finalize();

        $prop = new \ReflectionProperty($route, 'stack');
        $prop->setAccessible(true);

        $this->assertCount(2, $prop->getValue($route));
    }

    public function testRefinalizing()
    {
        $route = $this->routeFactory();

        $mw = function ($req, $res, $next) {
            return $res;
        };
        $route->add($mw);

        $route->finalize();
        $route->finalize();

        $prop = new \ReflectionProperty($route, 'stack');
        $prop->setAccessible(true);

        $this->assertCount(2, $prop->getValue($route));
    }


    public function testIdentifier()
    {
        $route = $this->routeFactory();
        $this->assertEquals('route0', $route->getIdentifier());
    }

    public function testSetName()
    {
        $route = $this->routeFactory();
        $this->assertEquals($route, $route->setName('foo'));
        $this->assertEquals('foo', $route->getName());
    }

    public function testSetInvalidName()
    {
        $route = $this->routeFactory();

        $this->expectException(\TypeError::class);

        $route->setName(false);
    }

    public function testSetOutputBuffering()
    {
        $route = $this->routeFactory();

        $route->setOutputBuffering('none');
        $this->assertEquals('none', $route->getOutputBuffering());

        $route->setOutputBuffering('append');
        $this->assertSame('append', $route->getOutputBuffering());

        $route->setOutputBuffering('prepend');
        $this->assertSame('prepend', $route->getOutputBuffering());
    }

    public function testSetInvalidOutputBuffering()
    {
        $route = $this->routeFactory();

        $this->expectException(\InvalidArgumentException::class);

        $route->setOutputBuffering('invalid');
    }

    /**
     * Ensure that the response returned by a route callable is the response
     * object that is returned by __invoke().
     */
    public function testInvokeWhenReturningAResponse()
    {
        $callable = function ($req, $res, $args) {
            return $res->write('foo');
        };
        $route = new Route(['GET'], '/', $callable);

        $env = Environment::mock();
        $uri = Uri::createFromString('https://example.com:80');
        $headers = new Headers();
        $cookies = [];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response;

        $response = $route->__invoke($request, $response);

        $this->assertEquals('foo', (string)$response->getBody());
    }

    /**
     * Ensure that anything echo'd in a route callable is added to the response
     * object that is returned by __invoke().
     */
    public function testInvokeWhenEchoingOutput()
    {
        $callable = function ($req, $res, $args) {
            echo "foo";
            return $res->withStatus(201);
        };
        $route = new Route(['GET'], '/', $callable);

        $env = Environment::mock();
        $uri = Uri::createFromString('https://example.com:80');
        $headers = new Headers();
        $cookies = [];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response;

        $response = $route->__invoke($request, $response);

        $this->assertEquals('foo', (string)$response->getBody());
        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * Ensure that if a string is returned by a route callable, then it is
     * added to the response object that is returned by __invoke().
     */
    public function testInvokeWhenReturningAString()
    {
        $callable = function ($req, $res, $args) {
            return "foo";
        };
        $route = new Route(['GET'], '/', $callable);

        $env = Environment::mock();
        $uri = Uri::createFromString('https://example.com:80');
        $headers = new Headers();
        $cookies = [];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response;

        $response = $route->__invoke($request, $response);

        $this->assertEquals('foo', (string)$response->getBody());
    }

    /**
     * Ensure that if `outputBuffering` property is set to `prepend` correct response
     * body is returned by __invoke().
     */
    public function testInvokeWhenPrependingOutputBuffer()
    {
        $callable = function ($req, $res, $args) {
            echo 'foo';
            return $res->write('bar');
        };
        $route = new Route(['GET'], '/', $callable);
        $route->setOutputBuffering('prepend');

        $env = Environment::mock();
        $uri = Uri::createFromString('https://example.com:80');
        $headers = new Headers();
        $cookies = [];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response;

        $response = $route->__invoke($request, $response);

        $this->assertEquals('foobar', (string)$response->getBody());
    }

    /**
     * @expectedException \Exception
     */
    public function testInvokeWithException()
    {
        $callable = function ($req, $res, $args) {
            throw new \Exception();
        };
        $route = new Route(['GET'], '/', $callable);


        $env = Environment::mock();
        $uri = Uri::createFromString('https://example.com:80');
        $headers = new Headers();
        $cookies = [];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response;

        $response = $route->__invoke($request, $response);
    }


    /**
     * Ensure that if `outputBuffering` property is set to `false` correct response
     * body is returned by __invoke().
     */
    public function testInvokeWhenDisablingOutputBuffer()
    {
        ob_start();
        $callable = function ($req, $res, $args) {
            echo 'foo';
            return $res->write('bar');
        };
        $route = new Route(['GET'], '/', $callable);
        $route->setOutputBuffering('none');

        $env = Environment::mock();
        $uri = Uri::createFromString('https://example.com:80');
        $headers = new Headers();
        $cookies = [];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response;

        $response = $route->__invoke($request, $response);

        $this->assertEquals('bar', (string)$response->getBody());

        $output = ob_get_clean();
        $this->assertEquals('foo', $output);
    }

    /**
     * Ensure that the pattern can be dynamically changed
     */
    public function testPatternCanBeChanged()
    {
        $route = $this->routeFactory();
        $route->setPattern('/hola/{nombre}');
        $this->assertEquals('/hola/{nombre}', $route->getPattern());
    }
}
