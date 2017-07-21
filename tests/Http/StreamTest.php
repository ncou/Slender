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

use Slender\Http\Stream;
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{
    /**
     * @var resource pipe stream file handle
     */
    private $pipeFh;

    /**
     * @var Stream
     */
    private $pipeStream;

    public function tearDown()
    {
        if ($this->pipeFh != null) {
            stream_get_contents($this->pipeFh); // prevent broken pipe error message
        }
    }

    /**
     * @covers Slender\Http\Stream::isPipe
     */
    public function testIsPipe()
    {
        $this->openPipeStream();

        $this->assertTrue($this->pipeStream->isPipe());

        $this->pipeStream->detach();
        $this->assertFalse($this->pipeStream->isPipe());

        $fhFile = fopen(__FILE__, 'r');
        $fileStream = new Stream($fhFile);
        $this->assertFalse($fileStream->isPipe());
    }

    /**
     * @covers Slender\Http\Stream::isReadable
     */
    public function testIsPipeReadable()
    {
        $this->openPipeStream();

        $this->assertTrue($this->pipeStream->isReadable());
    }

    /**
     * @covers Slender\Http\Stream::isSeekable
     */
    public function testPipeIsNotSeekable()
    {
        $this->openPipeStream();

        $this->assertFalse($this->pipeStream->isSeekable());
    }

    /**
     * @covers Slender\Http\Stream::seek
     * @expectedException \RuntimeException
     */
    public function testCannotSeekPipe()
    {
        $this->openPipeStream();

        $this->pipeStream->seek(0);
    }

    /**
     * @covers Slender\Http\Stream::tell
     * @expectedException \RuntimeException
     */
    public function testCannotTellPipe()
    {
        $this->openPipeStream();

        $this->pipeStream->tell();
    }

    /**
     * @covers Slender\Http\Stream::rewind
     * @expectedException \RuntimeException
     */
    public function testCannotRewindPipe()
    {
        $this->openPipeStream();

        $this->pipeStream->rewind();
    }

    /**
     * @covers Slender\Http\Stream::getSize
     */
    public function testPipeGetSizeYieldsNull()
    {
        $this->openPipeStream();

        $this->assertNull($this->pipeStream->getSize());
    }

    /**
     * @covers Slender\Http\Stream::close
     */
    public function testClosePipe()
    {
        $this->openPipeStream();

        stream_get_contents($this->pipeFh); // prevent broken pipe error message
        $this->pipeStream->close();
        $this->pipeFh = null;

        $this->assertFalse($this->pipeStream->isPipe());
    }

    /**
     * @covers Slender\Http\Stream::__toString
     */
    public function testPipeToString()
    {
        $this->openPipeStream();

        $this->assertSame('', (string) $this->pipeStream);
    }

    /**
     * @covers Slender\Http\Stream::getContents
     */

    public function testPipeGetContents()
    {
        $this->openPipeStream();

        $contents = trim($this->pipeStream->getContents());
        $this->assertSame('12', $contents);
    }

    /**
     * Opens the pipe stream
     *
     * @see StreamTest::pipeStream
     */
    private function openPipeStream()
    {
        $this->pipeFh = popen('echo 12', 'r');
        $this->pipeStream = new Stream($this->pipeFh);
    }
}
