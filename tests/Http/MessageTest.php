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
namespace Slender\Tests\Http;

use Slender\Http\Headers;
use Slender\Tests\Mocks\MessageStub;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    /*******************************************************************************
     * Protocol
     ******************************************************************************/

    /**
     * @covers Slender\Http\Message::getProtocolVersion
     */
    public function testGetProtocolVersion()
    {
        $message = new MessageStub();
        $message->protocolVersion = '1.0';

        $this->assertEquals('1.0', $message->getProtocolVersion());
    }

    /**
     * @covers Slender\Http\Message::withProtocolVersion
     */
    public function testWithProtocolVersion()
    {
        $message = new MessageStub();
        $clone = $message->withProtocolVersion('1.0');

        $this->assertEquals('1.0', $clone->protocolVersion);
    }

    /**
     * @covers Slender\Http\Message::withProtocolVersion
     * @expectedException \InvalidArgumentException
     */
    public function testWithProtocolVersionInvalidThrowsException()
    {
        $message = new MessageStub();
        $message->withProtocolVersion('3.0');
    }

    /*******************************************************************************
     * Headers
     ******************************************************************************/

    /**
     * @covers Slender\Http\Message::getHeaders
     */
    public function testGetHeaders()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');
        $headers->add('X-Foo', 'two');
        $headers->add('X-Foo', 'three');

        $message = new MessageStub();
        $message->headers = $headers;

        $shouldBe = [
            'X-Foo' => [
                'one',
                'two',
                'three',
            ],
        ];

        $this->assertEquals($shouldBe, $message->getHeaders());
    }

    /**
     * @covers Slender\Http\Message::hasHeader
     */
    public function testHasHeader()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');

        $message = new MessageStub();
        $message->headers = $headers;

        $this->assertTrue($message->hasHeader('X-Foo'));
        $this->assertFalse($message->hasHeader('X-Bar'));
    }

    /**
     * @covers Slender\Http\Message::getHeaderLine
     */
    public function testGetHeaderLine()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');
        $headers->add('X-Foo', 'two');
        $headers->add('X-Foo', 'three');

        $message = new MessageStub();
        $message->headers = $headers;

        $this->assertEquals('one,two,three', $message->getHeaderLine('X-Foo'));
        $this->assertEquals('', $message->getHeaderLine('X-Bar'));
    }

    /**
     * @covers Slender\Http\Message::getHeader
     */
    public function testGetHeader()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');
        $headers->add('X-Foo', 'two');
        $headers->add('X-Foo', 'three');

        $message = new MessageStub();
        $message->headers = $headers;

        $this->assertEquals(['one', 'two', 'three'], $message->getHeader('X-Foo'));
        $this->assertEquals([], $message->getHeader('X-Bar'));
    }

    /**
     * @covers Slender\Http\Message::withHeader
     */
    public function testWithHeader()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');
        $message = new MessageStub();
        $message->headers = $headers;
        $clone = $message->withHeader('X-Foo', 'bar');

        $this->assertEquals('bar', $clone->getHeaderLine('X-Foo'));
    }

    /**
     * @covers Slender\Http\Message::withAddedHeader
     */
    public function testWithAddedHeader()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');
        $message = new MessageStub();
        $message->headers = $headers;
        $clone = $message->withAddedHeader('X-Foo', 'two');

        $this->assertEquals('one,two', $clone->getHeaderLine('X-Foo'));
    }

    /**
     * @covers Slender\Http\Message::withoutHeader
     */
    public function testWithoutHeader()
    {
        $headers = new Headers();
        $headers->add('X-Foo', 'one');
        $headers->add('X-Bar', 'two');
        $response = new MessageStub();
        $response->headers = $headers;
        $clone = $response->withoutHeader('X-Foo');
        $shouldBe = [
            'X-Bar' => ['two'],
        ];

        $this->assertEquals($shouldBe, $clone->getHeaders());
    }

    /*******************************************************************************
     * Body
     ******************************************************************************/

    /**
     * @covers Slender\Http\Message::getBody
     */
    public function testGetBody()
    {
        $body = $this->getBody();
        $message = new MessageStub();
        $message->body = $body;

        $this->assertSame($body, $message->getBody());
    }

    /**
     * @covers Slender\Http\Message::withBody
     */
    public function testWithBody()
    {
        $body = $this->getBody();
        $body2 = $this->getBody();
        $message = new MessageStub();
        $message->body = $body;
        $clone = $message->withBody($body2);

        $this->assertSame($body, $message->body);
        $this->assertSame($body2, $clone->body);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Slender\Http\Body
     */
    protected function getBody()
    {
        return $this->getMockBuilder('Slender\Http\Body')->disableOriginalConstructor()->getMock();
    }
}
