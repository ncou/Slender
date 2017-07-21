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

use Slender\Http\Environment;
use PHPUnit\Framework\TestCase;

class EnvironmentTest extends TestCase
{
    /**
     * Server settings for the default HTTP request
     * used by this script's tests.
     */
    public function setUp()
    {
        $_SERVER['DOCUMENT_ROOT'] = '/var/www';
        $_SERVER['SCRIPT_NAME'] = '/foo/index.php';
        $_SERVER['REQUEST_URI'] = '/foo/index.php/bar/xyz';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['SERVER_NAME'] = 'slender';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING'] = 'one=1&two=2&three=3';
        $_SERVER['HTTPS'] = '';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    /**
     * Test environment from globals
     */
    public function testEnvironmentFromGlobals()
    {
        $env = new Environment($_SERVER);

        $this->assertEquals($_SERVER, $env->all());
    }

    /**
     * Test environment from mock data
     */
    public function testMock()
    {
        $env = Environment::mock([
            'SCRIPT_NAME' => '/foo/bar/index.php',
            'REQUEST_URI' => '/foo/bar?abc=123',
        ]);

        $this->assertInstanceOf('\Slender\Interfaces\CollectionInterface', $env);
        $this->assertEquals('/foo/bar/index.php', $env->get('SCRIPT_NAME'));
        $this->assertEquals('/foo/bar?abc=123', $env->get('REQUEST_URI'));
        $this->assertEquals('localhost', $env->get('HTTP_HOST'));
    }
}
