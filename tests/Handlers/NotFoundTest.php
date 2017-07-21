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
namespace Slender\Tests\Handlers;

use Slender\Handlers\NotFound;
use Slender\Http\Request;
use Slender\Http\Response;
use Slender\Http\Uri;
use PHPUnit\Framework\TestCase;

class NotFoundTest extends TestCase
{
    public function notFoundProvider()
    {
        return [
            ['application/json', 'application/json', '{'],
            ['application/vnd.api+json', 'application/json', '{'],
            ['application/xml', 'application/xml', '<root>'],
            ['application/hal+xml', 'application/xml', '<root>'],
            ['text/xml', 'text/xml', '<root>'],
            ['text/html', 'text/html', '<html>'],
        ];
    }

    /**
     * Test invalid method returns the correct code and content type
     *
     * @dataProvider notFoundProvider
     */
    public function testNotFound($acceptHeader, $contentType, $startOfBody)
    {
        $notAllowed = new NotFound();

        /** @var Response $res */
        $res = $notAllowed->__invoke($this->getRequest('GET', $acceptHeader), new Response(), ['POST', 'PUT']);

        $this->assertSame(404, $res->getStatusCode());
        $this->assertSame($contentType, $res->getHeaderLine('Content-Type'));
        $this->assertEquals(0, strpos((string)$res->getBody(), $startOfBody));
    }

    public function testNotFoundContentType()
    {
        $errorMock = $this->getMockBuilder(NotFound::class)->setMethods(['determineContentType'])->getMock();
        $errorMock->method('determineContentType')
            ->will($this->returnValue('unknown/type'));

        /** @var Request $req */
        $req = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

        $this->expectException(\UnexpectedValueException::class);

        /** @var NotFound $errorMock */
        $errorMock($req, new Response(), ['POST']);
    }

    /**
     * @param string $method
     * @return \PHPUnit_Framework_MockObject_MockObject|\Slender\Http\Request
     */
    protected function getRequest($method, $contentType = 'text/html')
    {
        $uri = new Uri('http', 'example.com', 80, '/notfound');

        $req = $this->getMockBuilder('Slender\Http\Request')->disableOriginalConstructor()->getMock();
        $req->expects($this->once())->method('getHeaderLine')->will($this->returnValue($contentType));
        $req->expects($this->any())->method('getUri')->will($this->returnValue($uri));

        return $req;
    }
}
