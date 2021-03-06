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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionProperty;
use Slender\Http\Body;
use Slender\Http\Headers;
use Slender\Http\Request;
use Slender\Http\Response;
use Slender\Http\Uri;
use Slender\Tests\Mocks\Stackable;
use PHPUnit\Framework\TestCase;

class MiddlewareAwareTest extends TestCase
{
    public function testSeedsMiddlewareStack()
    {
        $stack = new Stackable;
        $stack->add(function ($req, $res, $next) {
            return $res->write('Hi');
        });
        $prop = new ReflectionProperty($stack, 'stack');
        $prop->setAccessible(true);

        $this->assertSame($stack, $prop->getValue($stack)->bottom());
    }

    public function testCallMiddlewareStack()
    {
        // Build middleware stack
        $stack = new Stackable;
        $stack->add(function ($req, $res, $next) {
            $res->write('In1');
            $res = $next($req, $res);
            $res->write('Out1');

            return $res;
        })->add(function ($req, $res, $next) {
            $res->write('In2');
            $res = $next($req, $res);
            $res->write('Out2');

            return $res;
        });

        // Request
        $uri = Uri::createFromString('https://example.com:443/foo/bar?abc=123');
        $headers = new Headers();
        $cookies = [];
        $serverParams = [];
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);

        // Response
        $response = new Response();

        // Invoke call stack
        $res = $stack->callMiddlewareStack($request, $response);

        $this->assertEquals('In2In1CenterOut1Out2', (string)$res->getBody());
    }

    public function testMiddlewareStackWithAStatic()
    {
        // Build middleware stack
        $stack = new Stackable;
        $stack->add('Slender\Tests\Mocks\StaticCallable::run')
            ->add(function ($req, $res, $next) {
                $res->write('In2');
                $res = $next($req, $res);
                $res->write('Out2');

                return $res;
            });

        // Request
        $uri = Uri::createFromString('https://example.com:443/foo/bar?abc=123');
        $headers = new Headers();
        $cookies = [];
        $serverParams = [];
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);

        // Response
        $response = new Response();

        // Invoke call stack
        $res = $stack->callMiddlewareStack($request, $response);

        $this->assertEquals('In2In1CenterOut1Out2', (string)$res->getBody());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testMiddlewareBadReturnValue()
    {
        // Build middleware stack
        $stack = new Stackable;
        $stack->add(function ($req, $res, $next) {
            $res = $res->write('In1');
            $res = $next($req, $res);
            $res = $res->write('Out1');

            // NOTE: No return value here
        });

        // Request
        $uri = Uri::createFromString('https://example.com:443/foo/bar?abc=123');
        $headers = new Headers();
        $cookies = [];
        $serverParams = [];
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);

        // Response
        $response = new Response();

        // Invoke call stack
        $stack->callMiddlewareStack($request, $response);
    }

    public function testAlternativeSeedMiddlewareStack()
    {
        $stack = new Stackable;
        $stack->alternativeSeed();
        $prop = new ReflectionProperty($stack, 'stack');
        $prop->setAccessible(true);

        $this->assertSame([$stack, 'testMiddlewareKernel'], $prop->getValue($stack)->bottom());
    }


    public function testAddMiddlewareWhileStackIsRunningThrowException()
    {
        $stack = new Stackable;
        $stack->add(function ($req, $resp) use ($stack) {
            $stack->add(function ($req, $resp) {
                return $resp;
            });
            return $resp;
        });
        $this->expectException(\RuntimeException::class);
        $stack->callMiddlewareStack(
            $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(ResponseInterface::class)->disableOriginalConstructor()->getMock()
        );
    }

    public function testSeedTwiceThrowException()
    {
        $stack = new Stackable;
        $stack->alternativeSeed();
        $this->expectException(\RuntimeException::class);
        $stack->alternativeSeed();
    }
}
