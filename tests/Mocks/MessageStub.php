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
namespace Slender\Tests\Mocks;

use Slender\Http\Message;

/**
 * Mock object for Slender\Http\MessageTest
 */
class MessageStub extends Message
{
    /**
     * Protocol version
     *
     * @var string
     */
    public $protocolVersion;

    /**
     * Headers
     *
     * @var \Slender\Interfaces\Http\HeadersInterface
     */
    public $headers;

    /**
     * Body object
     *
     * @var \Psr\Http\Message\StreamInterface
     */
    public $body;
}
