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

use Slender\Handlers\NotAllowed;
use Slender\Http\Response;
use PHPUnit\Framework\TestCase;

class NotAllowedTest extends TestCase
{
    public function invalidMethodProvider()
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
     * @dataProvider invalidMethodProvider
     */
    public function testInvalidMethod($acceptHeader, $contentType, $startOfBody)
    {
        $notAllowed = new NotAllowed();

        /** @var Response $res */
        $res = $notAllowed->__invoke($this->getRequest('GET', $acceptHeader), new Response(), ['POST', 'PUT']);

        $this->assertSame(405, $res->getStatusCode());
        $this->assertTrue($res->hasHeader('Allow'));
        $this->assertSame($contentType, $res->getHeaderLine('Content-Type'));
        $this->assertEquals('POST, PUT', $res->getHeaderLine('Allow'));
        $this->assertEquals(0, strpos((string)$res->getBody(), $startOfBody));
    }

    public function testOptions()
    {
        $notAllowed = new NotAllowed();

        /** @var Response $res */
        $res = $notAllowed->__invoke($this->getRequest('OPTIONS'), new Response(), ['POST', 'PUT']);

        $this->assertSame(200, $res->getStatusCode());
        $this->assertTrue($res->hasHeader('Allow'));
        $this->assertEquals('POST, PUT', $res->getHeaderLine('Allow'));
    }

    public function testNotFoundContentType()
    {
        $errorMock = $this->getMockBuilder(NotAllowed::class)->setMethods(['determineContentType'])->getMock();
        $errorMock->method('determineContentType')
            ->will($this->returnValue('unknown/type'));

        $this->expectException('\UnexpectedValueException');

        /**
         * @var NotAllowed $errorMock
         */
        $errorMock($this->getRequest('GET', 'unknown/type'), new Response(), ['POST']);
    }

    /**
     * @param string $method
     * @return \PHPUnit_Framework_MockObject_MockObject|\Slender\Http\Request
     */
    protected function getRequest($method, $contentType = 'text/html')
    {
        $req = $this->getMockBuilder('Slender\Http\Request')->disableOriginalConstructor()->getMock();
        $req->expects($this->once())->method('getMethod')->will($this->returnValue($method));
        $req->expects($this->any())->method('getHeaderLine')->will($this->returnValue($contentType));

        return $req;
    }
}
