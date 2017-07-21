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

use Slender\Handlers\PhpError;
use Slender\Http\Request;
use Slender\Http\Response;
use PHPUnit\Framework\TestCase;

class PhpErrorTest extends TestCase
{
    public function phpErrorProvider()
    {
        return [
            ['application/json', 'application/json', '{'],
            ['application/vnd.api+json', 'application/json', '{'],
            ['application/xml', 'application/xml', '<error>'],
            ['application/hal+xml', 'application/xml', '<error>'],
            ['text/xml', 'text/xml', '<error>'],
            ['text/html', 'text/html', '<html>'],
        ];
    }

    /**
     * Test invalid method returns the correct code and content type
     *
     * @requires PHP 7.0
     * @dataProvider phpErrorProvider
     */
    public function testPhpError($acceptHeader, $contentType, $startOfBody)
    {
        $error = new PhpError();

        /** @var Response $res */
        $res = $error->__invoke($this->getRequest('GET', $acceptHeader), new Response(), new \Exception());

        $this->assertSame(500, $res->getStatusCode());
        $this->assertSame($contentType, $res->getHeaderLine('Content-Type'));
        $this->assertEquals(0, strpos((string)$res->getBody(), $startOfBody));
    }

    /**
     * Test invalid method returns the correct code and content type
     *
     * @requires PHP 7.0
     * @dataProvider phpErrorProvider
     */
    public function testPhpErrorDisplayDetails($acceptHeader, $contentType, $startOfBody)
    {
        $error = new PhpError(true);

        $exception = new \Exception('Oops', 1, new \Exception('Opps before'));

        /** @var Response $res */
        $res = $error->__invoke($this->getRequest('GET', $acceptHeader), new Response(), $exception);

        $this->assertSame(500, $res->getStatusCode());
        $this->assertSame($contentType, $res->getHeaderLine('Content-Type'));
        $this->assertEquals(0, strpos((string)$res->getBody(), $startOfBody));
    }

    /**
     * @requires PHP 7.0
     */
    public function testNotFoundContentType()
    {
        $errorMock = $this->getMockBuilder(PhpError::class)->setMethods(['determineContentType'])->getMock();
        $errorMock->method('determineContentType')
            ->will($this->returnValue('unknown/type'));

        /** @var Request $req */
        $req = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->expectExceptionMessage('Cannot render unknown content type unknown/type');

        /** @var PhpError $errorMock */
        $errorMock($req, new Response(), new \Exception());
    }

    /**
     * @param string $method
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Slender\Http\Request
     */
    protected function getRequest($method, $acceptHeader)
    {
        $req = $this->getMockBuilder('Slender\Http\Request')->disableOriginalConstructor()->getMock();
        $req->expects($this->once())->method('getHeaderLine')->will($this->returnValue($acceptHeader));

        return $req;
    }
}
