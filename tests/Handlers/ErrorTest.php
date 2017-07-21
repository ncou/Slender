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

use Slender\Handlers\Error;
use Slender\Http\Request;
use Slender\Http\Response;
use PHPUnit\Framework\TestCase;

class ErrorTest extends TestCase
{
    public function errorProvider()
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
     * @dataProvider errorProvider
     */
    public function testError($acceptHeader, $contentType, $startOfBody)
    {
        $error = new Error();
        $e = new \Exception("Oops", 1, new \Exception('Previous oops'));

        /** @var Response $res */
        $res = $error->__invoke($this->getRequest('GET', $acceptHeader), new Response(), $e);

        $this->assertSame(500, $res->getStatusCode());
        $this->assertSame($contentType, $res->getHeaderLine('Content-Type'));
        $this->assertEquals(0, strpos((string)$res->getBody(), $startOfBody));
    }

    /**
     * Test invalid method returns the correct code and content type with details
     *
     * @dataProvider errorProvider
     */
    public function testErrorDisplayDetails($acceptHeader, $contentType, $startOfBody)
    {
        $error = new Error(true);
        $e = new \Exception('Oops', 1, new \Exception('Opps before'));

        /** @var Response $res */
        $res = $error->__invoke($this->getRequest('GET', $acceptHeader), new Response(), $e);

        $this->assertSame(500, $res->getStatusCode());
        $this->assertSame($contentType, $res->getHeaderLine('Content-Type'));
        $this->assertEquals(0, strpos((string)$res->getBody(), $startOfBody));
    }

    public function testNotFoundContentType()
    {
        $errorMock = $this->getMockBuilder(Error::class)->setMethods(['determineContentType'])->getMock();
        $errorMock->method('determineContentType')
            ->will($this->returnValue('unknown/type'));

        $e = new \Exception("Oops");

        /** @var Request $req */
        $req = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Cannot render unknown content type unknown/type');

        /** @var Error $errorMock */
        $errorMock($req, new Response(), $e);
    }

    /**
     * Test that an exception with a previous exception provides correct output
     * to the error log
     */
    public function testPreviousException()
    {
        $error = $this->getMockBuilder('\Slender\Handlers\Error')->setMethods(['logError'])->getMock();
        $error->expects($this->once())->method('logError')->with(
            $this->logicalAnd(
                $this->stringContains("Type: Exception" . PHP_EOL . "Message: Second Oops"),
                $this->stringContains("Previous Error:" . PHP_EOL . "Type: Exception" . PHP_EOL . "Message: First Oops")
            )
        );

        $first = new \Exception("First Oops");
        $second = new \Exception("Second Oops", 0, $first);

        $error->__invoke($this->getRequest('GET', 'application/json'), new Response(), $second);
    }

    /**
     * If someone extends the Error handler and calls renderHtmlExceptionOrError with
     * a parameter that isn't an Exception or Error, then we thrown an Exception.
     */
    public function testRenderHtmlExceptionorErrorTypeChecksParameter()
    {
        $class = new \ReflectionClass(Error::class);
        $renderHtmlExceptionorError = $class->getMethod('renderHtmlExceptionOrError');
        $renderHtmlExceptionorError->setAccessible(true);

        $this->expectException(\TypeError::class);

        $error = new Error();
        $renderHtmlExceptionorError->invokeArgs($error, ['foo']);
    }

    /**
     * @param string $method
     * @return \PHPUnit_Framework_MockObject_MockObject|\Slender\Http\Request
     */
    protected function getRequest($method, $acceptHeader)
    {
        $req = $this->getMockBuilder('Slender\Http\Request')->disableOriginalConstructor()->getMock();
        $req->expects($this->once())->method('getHeaderLine')->will($this->returnValue($acceptHeader));

        return $req;
    }
}
