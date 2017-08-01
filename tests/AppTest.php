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

use DI\Container;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Slender\App;
use Slender\Exception\InvalidMethodException;
use Slender\Exception\MethodNotAllowedException;
use Slender\Exception\NotFoundException;
use Slender\Handlers\Strategies\RequestResponseArgs;
use Slender\Http\Body;
use Slender\Http\Environment;
use Slender\Http\Headers;
use Slender\Http\Request;
use Slender\Http\RequestBody;
use Slender\Http\Response;
use Slender\Http\Uri;
use Slender\Route;
use Slender\Router;
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
    public static function setupBeforeClass()
    {
        ini_set('error_log', tempnam(sys_get_temp_dir(), 'slender'));
    }

    /**
     * @expectedException \TypeError
     */
    public function testContainerInterfaceException()
    {
        $app = $this->appFactory('');
    }

    public function testIssetInContainer()
    {
        $app = $this->appFactory();
        $c = $app->getContainer();
        $router = $c->get('router');
        $this->assertTrue(isset($router));
    }

    public function testInvalidNumberArgs()
    {
        $this->expectException(\AssertionError::class);

        $app = $this->appFactory();
        $callable = function () {
            return false;
        };

        $app->get('/bogus', $callable, 'bogus');
    }

    /********************************************************************************
     * Router proxy methods
     *******************************************************************************/

    public function testGetRoute()
    {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = $this->appFactory();
        $route = $app->get($path, $callable);

        $this->assertInstanceOf('\Slender\Route', $route);
        $this->assertAttributeContains('GET', 'methods', $route);
    }

    public function testPostRoute()
    {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = $this->appFactory();
        $route = $app->post($path, $callable);

        $this->assertInstanceOf('\Slender\Route', $route);
        $this->assertAttributeContains('POST', 'methods', $route);
    }

    public function testPutRoute()
    {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };

        $app = $this->appFactory();
        $route = $app->put($path, $callable);

        $this->assertInstanceOf('\Slender\Route', $route);
        $this->assertAttributeContains('PUT', 'methods', $route);
    }

    public function testPatchRoute()
    {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = $this->appFactory();
        $route = $app->patch($path, $callable);

        $this->assertInstanceOf('\Slender\Route', $route);
        $this->assertAttributeContains('PATCH', 'methods', $route);
    }

    public function testDeleteRoute()
    {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = $this->appFactory();
        $route = $app->delete($path, $callable);

        $this->assertInstanceOf('\Slender\Route', $route);
        $this->assertAttributeContains('DELETE', 'methods', $route);
    }

    public function testOptionsRoute()
    {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = $this->appFactory();
        $route = $app->options($path, $callable);

        $this->assertInstanceOf('\Slender\Route', $route);
        $this->assertAttributeContains('OPTIONS', 'methods', $route);
    }

    public function testAnyRoute()
    {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = $this->appFactory();
        $route = $app->any($path, $callable);

        $this->assertInstanceOf('\Slender\Route', $route);
        $this->assertAttributeContains('GET', 'methods', $route);
        $this->assertAttributeContains('POST', 'methods', $route);
        $this->assertAttributeContains('PUT', 'methods', $route);
        $this->assertAttributeContains('PATCH', 'methods', $route);
        $this->assertAttributeContains('DELETE', 'methods', $route);
        $this->assertAttributeContains('OPTIONS', 'methods', $route);
    }

    public function testMapRoute()
    {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = $this->appFactory();
        $route = $app->map(['GET', 'POST'], $path, $callable);

        $this->assertInstanceOf('\Slender\Route', $route);
        $this->assertAttributeContains('GET', 'methods', $route);
        $this->assertAttributeContains('POST', 'methods', $route);
    }

    /********************************************************************************
     * Route Patterns
     *******************************************************************************/
    public function testSegmentRouteThatDoesNotEndInASlash()
    {
        $app = $this->appFactory();
        $app->get('/foo', function ($req, $res) {
            // Do something
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/foo', 'pattern', $router->lookupRoute('route0'));
    }

    public function testSegmentRouteThatEndsInASlash()
    {
        $app = $this->appFactory();
        $app->get('/foo/', function ($req, $res) {
            // Do something
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/foo/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testSegmentRouteThatDoesNotStartWithASlash()
    {
        $app = $this->appFactory();
        $app->get('foo', function ($req, $res) {
            // Do something
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('foo', 'pattern', $router->lookupRoute('route0'));
    }

    public function testSingleSlashRoute()
    {
        $app = $this->appFactory();
        $app->get('/', function ($req, $res) {
            // Do something
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyRoute()
    {
        $app = $this->appFactory();
        $app->get('', function ($req, $res) {
            // Do something
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('', 'pattern', $router->lookupRoute('route0'));
    }

    /********************************************************************************
     * Route Groups
     *******************************************************************************/
    public function testGroupSegmentWithSegmentRouteThatDoesNotEndInASlash()
    {
        $app = $this->appFactory();
        $app->group('/foo', function () use ($app) {
            $app->get('/bar', function ($req, $res) {
                // Do something
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/foo/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSegmentWithSegmentRouteThatEndsInASlash()
    {
        $app = $this->appFactory();
        $app->group('/foo', function () use ($app) {
            $app->get('/bar/', function ($req, $res) {
                // Do something
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/foo/bar/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSegmentWithSingleSlashRoute()
    {
        $app = $this->appFactory();
        $app->group('/foo', function () use ($app) {
            $app->get('/', function ($req, $res) {
                // Do something
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/foo/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSegmentWithEmptyRoute()
    {
        $app = $this->appFactory();
        $app->group('/foo', function () use ($app) {
            $app->get('', function ($req, $res) {
                // Do something
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/foo', 'pattern', $router->lookupRoute('route0'));
    }

    public function testTwoGroupSegmentsWithSingleSlashRoute()
    {
        $app = $this->appFactory();
        $app->group('/foo', function () use ($app) {
            $app->group('/baz', function () use ($app) {
                $app->get('/', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/foo/baz/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testTwoGroupSegmentsWithAnEmptyRoute()
    {
        $app = $this->appFactory();
        $app->group('/foo', function () use ($app) {
            $app->group('/baz', function () use ($app) {
                $app->get('', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/foo/baz', 'pattern', $router->lookupRoute('route0'));
    }

    public function testTwoGroupSegmentsWithSegmentRoute()
    {
        $app = $this->appFactory();
        $app->group('/foo', function () use ($app) {
            $app->group('/baz', function () use ($app) {
                $app->get('/bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/foo/baz/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testTwoGroupSegmentsWithSegmentRouteThatHasATrailingSlash()
    {
        $app = $this->appFactory();
        $app->group('/foo', function () use ($app) {
            $app->group('/baz', function () use ($app) {
                $app->get('/bar/', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/foo/baz/bar/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSegmentWithSingleSlashNestedGroupAndSegmentRoute()
    {
        $app = $this->appFactory();
        $app->group('/foo', function () use ($app) {
            $app->group('/', function () use ($app) {
                $app->get('/bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/foo//bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSegmentWithSingleSlashGroupAndSegmentRouteWithoutLeadingSlash()
    {
        $app = $this->appFactory();
        $app->group('/foo', function () use ($app) {
            $app->group('/', function () use ($app) {
                $app->get('bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/foo/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSegmentWithEmptyNestedGroupAndSegmentRoute()
    {
        $app = $this->appFactory();
        $app->group('/foo', function () use ($app) {
            $app->group('', function () use ($app) {
                $app->get('/bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/foo/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSegmentWithEmptyNestedGroupAndSegmentRouteWithoutLeadingSlash()
    {
        $app = $this->appFactory();
        $app->group('/foo', function () use ($app) {
            $app->group('', function () use ($app) {
                $app->get('bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/foobar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithSegmentRouteThatDoesNotEndInASlash()
    {
        $app = $this->appFactory();
        $app->group('/', function () use ($app) {
            $app->get('/bar', function ($req, $res) {
                // Do something
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('//bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithSegmentRouteThatEndsInASlash()
    {
        $app = $this->appFactory();
        $app->group('/', function () use ($app) {
            $app->get('/bar/', function ($req, $res) {
                // Do something
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('//bar/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithSingleSlashRoute()
    {
        $app = $this->appFactory();
        $app->group('/', function () use ($app) {
            $app->get('/', function ($req, $res) {
                // Do something
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('//', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithEmptyRoute()
    {
        $app = $this->appFactory();
        $app->group('/', function () use ($app) {
            $app->get('', function ($req, $res) {
                // Do something
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithNestedGroupSegmentWithSingleSlashRoute()
    {
        $app = $this->appFactory();
        $app->group('/', function () use ($app) {
            $app->group('/baz', function () use ($app) {
                $app->get('/', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('//baz/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithNestedGroupSegmentWithAnEmptyRoute()
    {
        $app = $this->appFactory();
        $app->group('/', function () use ($app) {
            $app->group('/baz', function () use ($app) {
                $app->get('', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('//baz', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithNestedGroupSegmentWithSegmentRoute()
    {
        $app = $this->appFactory();
        $app->group('/', function () use ($app) {
            $app->group('/baz', function () use ($app) {
                $app->get('/bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('//baz/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithNestedGroupSegmentWithSegmentRouteThatHasATrailingSlash()
    {
        $app = $this->appFactory();
        $app->group('/', function () use ($app) {
            $app->group('/baz', function () use ($app) {
                $app->get('/bar/', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('//baz/bar/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithSingleSlashNestedGroupAndSegmentRoute()
    {
        $app = $this->appFactory();
        $app->group('/', function () use ($app) {
            $app->group('/', function () use ($app) {
                $app->get('/bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('///bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithSingleSlashGroupAndSegmentRouteWithoutLeadingSlash()
    {
        $app = $this->appFactory();
        $app->group('/', function () use ($app) {
            $app->group('/', function () use ($app) {
                $app->get('bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('//bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithEmptyNestedGroupAndSegmentRoute()
    {
        $app = $this->appFactory();
        $app->group('/', function () use ($app) {
            $app->group('', function () use ($app) {
                $app->get('/bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('//bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testGroupSingleSlashWithEmptyNestedGroupAndSegmentRouteWithoutLeadingSlash()
    {
        $app = $this->appFactory();
        $app->group('/', function () use ($app) {
            $app->group('', function () use ($app) {
                $app->get('bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithSegmentRouteThatDoesNotEndInASlash()
    {
        $app = $this->appFactory();
        $app->group('', function () use ($app) {
            $app->get('/bar', function ($req, $res) {
                // Do something
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithSegmentRouteThatEndsInASlash()
    {
        $app = $this->appFactory();
        $app->group('', function () use ($app) {
            $app->get('/bar/', function ($req, $res) {
                // Do something
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/bar/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithSingleSlashRoute()
    {
        $app = $this->appFactory();
        $app->group('', function () use ($app) {
            $app->get('/', function ($req, $res) {
                // Do something
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithEmptyRoute()
    {
        $app = $this->appFactory();
        $app->group('', function () use ($app) {
            $app->get('', function ($req, $res) {
                // Do something
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithNestedGroupSegmentWithSingleSlashRoute()
    {
        $app = $this->appFactory();
        $app->group('', function () use ($app) {
            $app->group('/baz', function () use ($app) {
                $app->get('/', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/baz/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithNestedGroupSegmentWithAnEmptyRoute()
    {
        $app = $this->appFactory();
        $app->group('', function () use ($app) {
            $app->group('/baz', function () use ($app) {
                $app->get('', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/baz', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithNestedGroupSegmentWithSegmentRoute()
    {
        $app = $this->appFactory();
        $app->group('', function () use ($app) {
            $app->group('/baz', function () use ($app) {
                $app->get('/bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/baz/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithNestedGroupSegmentWithSegmentRouteThatHasATrailingSlash()
    {
        $app = $this->appFactory();
        $app->group('', function () use ($app) {
            $app->group('/baz', function () use ($app) {
                $app->get('/bar/', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/baz/bar/', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithSingleSlashNestedGroupAndSegmentRoute()
    {
        $app = $this->appFactory();
        $app->group('', function () use ($app) {
            $app->group('/', function () use ($app) {
                $app->get('/bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('//bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithSingleSlashGroupAndSegmentRouteWithoutLeadingSlash()
    {
        $app = $this->appFactory();
        $app->group('', function () use ($app) {
            $app->group('/', function () use ($app) {
                $app->get('bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithEmptyNestedGroupAndSegmentRoute()
    {
        $app = $this->appFactory();
        $app->group('', function () use ($app) {
            $app->group('', function () use ($app) {
                $app->get('/bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('/bar', 'pattern', $router->lookupRoute('route0'));
    }

    public function testEmptyGroupWithEmptyNestedGroupAndSegmentRouteWithoutLeadingSlash()
    {
        $app = $this->appFactory();
        $app->group('', function () use ($app) {
            $app->group('', function () use ($app) {
                $app->get('bar', function ($req, $res) {
                    // Do something
                });
            });
        });
        /** @var \Slender\Router $router */
        $router = $app->getContainer()->get('router');
        $this->assertAttributeEquals('bar', 'pattern', $router->lookupRoute('route0'));
    }

    /********************************************************************************
     * Middleware
     *******************************************************************************/

    public function testBottomMiddlewareIsApp()
    {
        $app = $this->appFactory();
        $mw = function ($req, $res, $next) {
            return $res;
        };
        $app->add($mw);

        $prop = new \ReflectionProperty($app, 'stack');
        $prop->setAccessible(true);

        $this->assertEquals($app, $prop->getValue($app)->bottom());
    }

    public function testAddMiddleware()
    {
        $app = $this->appFactory();
        $mw = function ($req, $res, $next) {
            return $res;
        };
        $app->add($mw);

        $prop = new \ReflectionProperty($app, 'stack');
        $prop->setAccessible(true);

        $this->assertCount(2, $prop->getValue($app));
    }

    public function testAddMiddlewareOnRoute()
    {
        $config = include(__DIR__ . '/config.php');
        $config['environment'] = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/',
            'REQUEST_METHOD' => 'GET',
        ]);
        $app = $this->appFactory($config);

        $c = $app->getContainer();
        $req = $c->get('request');
        $res = $c->get('response');

        $app->get('/', function () use ($req, $res) {
            return $res->write('Center');
        })->add(function ($req, $res, $next) {
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


        // Invoke app
        $app($req, $res);

        $body = (string)$res->getBody();
        $this->assertEquals('In2In1CenterOut1Out2', $body);
    }


    public function testAddMiddlewareOnRouteGroup()
    {
        $config = include(__DIR__ . '/config.php');
        $config['environment'] = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/',
            'REQUEST_METHOD' => 'GET',
        ]);
        $app = $this->appFactory($config);

        $c = $app->getContainer();
        $request = $c->get('request');
        $response = $c->get('response');

        $app->group('/foo', function () use ($app, $request, $response) {
            $app->get('/', function ($request, $response) {
                return $response->write('Center');
            });
        })->add(function ($request, $response, $next) {
            $response->write('In1');
            $response = $next($request, $response);
            $response->write('Out1');

            return $response;
        })->add(function ($request, $response, $next) {
            $response->write('In2');
            $response = $next($request, $response);
            $response->write('Out2');

            return $response;
        });

        // Invoke app
        $app($request, $response);

        $this->assertEquals('In2In1CenterOut1Out2', (string)$response->getBody());
    }

    public function testAddMiddlewareOnTwoRouteGroup()
    {
        $app = $this->appFactory();

        $app->group('/foo', function () use ($app) {
            $app->group('/baz', function () use ($app) {
                $app->get('/', function ($request, $response) {
                    return $response->write('Center');
                });
            })->add(function ($request, $response, $next) {
                $response->write('In2');
                $response = $next($request, $response);
                $response->write('Out2');

                return $response;
            });
        })->add(function ($request, $response, $next) {
            $response->write('In1');
            $response = $next($request, $response);
            $response->write('Out1');

            return $response;
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/baz/',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response();

        // Invoke app
        $app($request, $response);

        $this->assertEquals('In1In2CenterOut2Out1', (string)$response->getBody());
    }

    public function testAddMiddlewareOnRouteAndOnTwoRouteGroup()
    {
        $app = $this->appFactory();
        $c = $app->getContainer();

        $app->group('/foo', function () use ($app) {
            $app->group('/baz', function () use ($app) {
                $c = $app->getContainer();
                $app->get('/', function ($request, $response) {
                    return $response->write('Center');
                })->add(function ($request, $response, $next) {
                    $response->write('In3');
                    $response = $next($request, $response);
                    $response->write('Out3');

                    return $response;
                });
            })->add(function ($request, $response, $next) {
                $response->write('In2');
                $response = $next($request, $response);
                $response->write('Out2');

                return $response;
            });
        })->add(function ($request, $response, $next) {
            $response->write('In1');
            $response = $next($request, $response);
            $response->write('Out1');

            return $response;
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/baz/',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response();

        // Invoke app
        $app($request, $response);

        $this->assertEquals('In1In2In3CenterOut3Out2Out1', (string)$response->getBody());
    }


    /********************************************************************************
     * Runner
     *******************************************************************************/

    public function testInvokeReturnMethodNotAllowed()
    {
        $app = $this->appFactory();
        $app->get('/foo', function ($req, $res) {
            $res->write('Hello');

            return $res;
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'POST',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('POST', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke app
        $resOut = $app($req, $res);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals(405, (int)$resOut->getStatusCode());
        $this->assertEquals(['GET'], $resOut->getHeader('Allow'));
        $this->assertContains(
            '<p>Method not allowed. Must be one of: <strong>GET</strong></p>',
            (string)$resOut->getBody()
        );

        // TODO: Move this to a separate test
        /*
        // now test that exception is raised if the handler isn't registered
        unset($app->getContainer()['notAllowedHandler']);
        $this->setExpectedException('Slender\Exception\MethodNotAllowedException');
        $app($req, $res);
        */
    }

    public function testInvokeWithMatchingRoute()
    {
        $config = include(__DIR__ . '/config.php');
        $config['environment'] = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);

        $app = $this->appFactory($config);
        $c = $app->getContainer();
        $req = $c->get('request');
        $res = $c->get('response');

        $app->get('/foo', function () use ($req, $res) {
            $res->write('Hello');

            return $res;
        });

        // Invoke app
        $resOut = $app($req, $res);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals('Hello', (string)$res->getBody());
    }

    public function testInvokeWithMatchingRouteWithSetArgument()
    {
        $config = include(__DIR__ . '/config.php');
        $config['environment'] = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'REQUEST_METHOD' => 'GET',
        ]);

        $app = $this->appFactory($config);
        $c = $app->getContainer();
        $request = $c->get('request');
        $response = $c->get('response');

        $app->get('/foo/bar', function ($request, $response, $attribute) {
            return $response->write("Hello $attribute");
        })->setArgument('attribute', 'world!');

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals('Hello world!', (string)$response->getBody());
    }

    public function testInvokeWithMatchingRouteWithSetArguments()
    {
        $app = $this->appFactory();
        $attributes = ['attribute1' => 'there', 'attribute2' => 'world!'];
        $app->get('/foo/bar', function ($request, $response, $attribute1, $attribute2) {
            return $response->write("Hello $attribute1 $attribute2");
        })->setArguments($attributes);

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response();

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals('Hello there world!', (string)$response->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameter()
    {
        $app = $this->appFactory();
        $app->get('/foo/{name}', function ($request, $response) {
            /**
             * @var Request $request
             * @var Route $route
             */
            $route = $request->getAttribute('route');
            $name = $route->getArgument('name');

            return $response->write("Hello $name");
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/test!',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response();

        // Invoke app
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals('Hello test!', (string)$response->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameterRequestResponseArgStrategy()
    {
        $config = include(__DIR__ . '/config.php');
        $config['environment'] = function () {
            return Environment::mock([
                'SCRIPT_NAME' => '/index.php',
                'REQUEST_URI' => '/foo/test!',
                'REQUEST_METHOD' => 'GET']);
        };

        $config['foundHandler'] = function (ContainerInterface $c) {
            return new RequestResponseArgs();
        };

        $app = $this->appFactory($config);
        $app->get('/foo/{name}', function ($req, $res, $name) {
            return $res->write("Hello {$name}");
        });

        // Invoke app
        $c = $app->getContainer();
        $req = $c->get('request');
        $res = $c->get('response');
        $resOut = $app($req, $res);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals('Hello test!', (string)$res->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameterOverwritesSetArgument()
    {
        $config = include(__DIR__ . '/config.php');
        $config['environment'] = function () {
            return Environment::mock([
                'SCRIPT_NAME' => '/index.php',
                'REQUEST_URI' => '/foo/test!',
                'REQUEST_METHOD' => 'GET']);
        };
        $app = $this->appFactory($config);
        $app->get('/foo/{name}', function ($request, $response, $extra, $name) {
            return $response->write("Hello $extra $name");
        })->setArguments(['extra' => 'there', 'name' => 'world!']);

        // Invoke app
        $c = $app->getContainer();
        $request = $c->get('request');
        $response = $c->get('response');
        $resOut = $app($request, $response);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals('Hello there test!', (string)$response->getBody());
    }

    public function testInvokeWithoutMatchingRoute()
    {
        $app = $this->appFactory();
        $app->get('/bar', function ($req, $res) {
            $res->write('Hello');

            return $res;
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke app
        $resOut = $app($req, $res);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertAttributeEquals(404, 'status', $resOut);

        /* TODO: Move this to it's own test.
         // now test that exception is raised if the handler isn't registered
        unset($app->getContainer()['notFoundHandler']);
        $this->setExpectedException('Slender\Exception\NotFoundException');
        $app($req, $res);
        */
    }

    public function testCurrentRequestAttributesAreNotLostWhenAddingRouteArguments()
    {
        $app = $this->appFactory();
        $app->get('/foo/{name}', function ($request, $response) {
            $route = $request->getAttribute('route');
            return $response->write($request->getAttribute('one') . $route->getArgument('name'));
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/rob',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $request = $request->withAttribute("one", 1);
        $response = new Response();


        // Invoke app
        $resOut = $app($request, $response);
        $this->assertEquals('1rob', (string)$resOut->getBody());
    }

    public function testCurrentRequestAttributesAreNotLostWhenAddingRouteArgumentsRequestResponseArg()
    {
        $config = include(__DIR__ . '/config.php');
        $config['environment'] = function () {
            return Environment::mock([
                'SCRIPT_NAME' => '/index.php',
                'REQUEST_URI' => '/foo/rob',
                'REQUEST_METHOD' => 'GET']);
        };

        $config['foundHandler'] = function (ContainerInterface $c) {
            return new RequestResponseArgs();
        };

        $app = $this->appFactory($config);
        $app->get('/foo/{name}', function ($req, $res, $name) {
            return $res->write($req->getAttribute('one') . $name);
        });

        $c = $app->getContainer();
        $req = $c->get('request')->withAttribute("one", 1);
        $res = $c->get('response');

        // Invoke app
        $resOut = $app($req, $res);
        $this->assertEquals('1rob', (string)$resOut->getBody());
    }

    public function testInvokeSubRequest()
    {
        $app = $this->appFactory();
        $app->get('/foo', function ($request, $response) {
            $response->write('foo');

            return $response;
        });

        $newResponse = $subReq = $app->subRequest('GET', '/foo');

        $this->assertEquals('foo', (string)$subReq->getBody());
        $this->assertEquals(200, $newResponse->getStatusCode());
    }

    public function testInvokeSubRequestWithQuery()
    {
        $app =$this->appFactory();
        $app->get('/foo', function ($request, $response) {
            $response->write("foo {$request->getParam('bar')}");

            return $response;
        });

        $subReq = $app->subRequest('GET', '/foo', 'bar=bar');

        $this->assertEquals('foo bar', (string)$subReq->getBody());
    }

    public function testInvokeSubRequestUsesResponseObject()
    {
        $app = $this->appFactory();
        $app->get('/foo', function ($request, $response) {
            $response->write("foo {$request->getParam('bar')}");

            return $response;
        });

        $resp = new Response(201);
        $newResponse = $subReq = $app->subRequest('GET', '/foo', 'bar=bar', [], [], '', $resp);

        $this->assertEquals('foo bar', (string)$subReq->getBody());
        $this->assertEquals(201, $newResponse->getStatusCode());
    }

    // TODO: Test finalize()

    public function testRun()
    {
        $app = $this->appFactory();

        $app->get('/foo', function ($request, $response) {
            echo 'bar';
        });

        ob_start();
        $app->run();
        $resOut = ob_get_clean();

        $this->assertEquals('bar', (string)$resOut);
    }

    public function testRespond()
    {
        $app = $this->appFactory();
        $app->get('/foo', function ($request, $response) {
            $response->write('Hello');
            return $response;
        });

        // Invoke app
        $c = $app->getContainer();
        $request = $c->get('request');
        $response = $c->get('response');
        $resOut = $app($request, $response);

        $app->respond($resOut);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->expectOutputString('Hello');
    }

    public function testRespondWithHeaderNotSent()
    {

        $app = $this->appFactory();
        $app->get('/foo', function ($request, $response) {
            $response->write('Hello');

            return $response;
        });

        // Invoke app
        $c = $app->getContainer();
        $request = $c->get('request');
        $response = $c->get('response');
        $resOut = $app($request, $response);

        $app->respond($resOut);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->expectOutputString('Hello');
    }

    public function testRespondNoContent()
    {
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response();

        $app = $this->appFactory();
        $app->get('/foo', function ($request, $response) {
            $response = $response->withStatus(204);
            return $response;
        });

        // Invoke app
        $resOut = $app($request, $response);

        $app->respond($resOut);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals([], $resOut->getHeader('Content-Type'));
        $this->assertEquals([], $resOut->getHeader('Content-Length'));
        $this->expectOutputString('');
    }

    public function testRespondWithPaddedStreamFilterOutput()
    {
        $availableFilter = stream_get_filters();

        if (version_compare(phpversion(), '7.0.0', '>=')) {
            $filterName           = 'string.rot13';
            $unfilterName         = 'string.rot13';
            $specificFilterName   = 'string.rot13';
            $specificUnfilterName = 'string.rot13';
        } else {
            $filterName           = 'mcrypt.*';
            $unfilterName         = 'mdecrypt.*';
            $specificFilterName   = 'mcrypt.rijndael-128';
            $specificUnfilterName = 'mdecrypt.rijndael-128';
        }

        if (in_array($filterName, $availableFilter) && in_array($unfilterName, $availableFilter)) {
            $config = include(__DIR__ . '/config.php');
            $config['environment'] = Environment::mock([
                'SCRIPT_NAME' => '/index.php',
                'REQUEST_URI' => '/foo',
                'REQUEST_METHOD' => 'GET',
            ]);
            $app = $this->appFactory($config);
            $app->get('/foo', function ($request, $response) use ($specificFilterName, $specificUnfilterName) {
                $key = base64_decode('xxxxxxxxxxxxxxxx');
                $iv = base64_decode('Z6wNDk9LogWI4HYlRu0mng==');

                $data = 'Hello';
                $length = strlen($data);

                $stream = fopen('php://temp', 'r+');

                $filter = stream_filter_append($stream, $specificFilterName, STREAM_FILTER_WRITE, [
                    'key' => $key,
                    'iv' => $iv
                ]);

                fwrite($stream, $data);
                rewind($stream);
                stream_filter_remove($filter);

                stream_filter_append($stream, $specificUnfilterName, STREAM_FILTER_READ, [
                    'key' => $key,
                    'iv' => $iv
                ]);

                return $response->withHeader('Content-Length', $length)->withBody(new Body($stream));
            });


            // Invoke app
            $c = $app->getContainer();
            $request = $c->get('request');
            $response = $c->get('response');
            $resOut = $app($request, $response);
            $app->respond($resOut);

            $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
            $this->expectOutputString('Hello');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testRespondIndeterminateLength()
    {
        $app = $this->appFactory();
        $body_stream = fopen('php://temp', 'r+');
        $response = new Response();
        $body = $this->getMockBuilder("\Slender\Http\Body")
            ->setMethods(["getSize"])
            ->setConstructorArgs([$body_stream])
            ->getMock();
        fwrite($body_stream, "Hello");
        rewind($body_stream);
        $body->method("getSize")->willReturn(null);
        $response = $response->withBody($body);
        $app->respond($response);
        $this->expectOutputString("Hello");
    }

    public function testRespondKnownLength()
    {
        $app = $this->appFactory();
        $body_stream = fopen('php://temp', 'r+');
        $response = new Response();
        $body = $this->getMockBuilder("\Slender\Http\Body")
            ->setMethods(["getSize"])
            ->setConstructorArgs([$body_stream])
            ->getMock();
        fwrite($body_stream, "Hello");
        rewind($body_stream);
        $body->method("getSize")->willReturn(5);
        $response = $response->withBody($body);
        $app->respond($response);
        $this->expectOutputString("Hello");
    }

    public function testResponseWithStreamReadYieldingLessBytesThanAsked()
    {
        $config = include(__DIR__ . '/config.php');
        $config['settings.responseChunkSize'] = Mocks\SmallChunksStream::CHUNK_SIZE * 2;
        $app = $this->appFactory($config);
        $app->get('/foo', function ($request, $response) {
            $response->write('Hello');

            return $response;
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new Mocks\SmallChunksStream();
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = (new Response())->withBody($body);

        // Invoke app
        $resOut = $app($request, $response);

        $app->respond($resOut);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->expectOutputString(str_repeat('.', Mocks\SmallChunksStream::SIZE));
    }

    public function testExceptionErrorHandlerDoesNotDisplayErrorDetails()
    {
        $app = $this->appFactory();

        $mw = function ($req, $res, $next) {
            throw new \Exception('middleware exception');
        };

        $app->add($mw);

        $app->get('/foo', function ($req, $res) {
            return $res;
        });

        $resOut = $app->run(true);

        $this->assertEquals(500, $resOut->getStatusCode());
        $this->assertNotRegExp('/.*middleware exception.*/', (string)$resOut);
    }

    /**
     * @requires PHP 7.0
     */
    public function testExceptionPhpErrorHandlerDoesNotDisplayErrorDetails()
    {
        $app = $this->appFactory();

        $mw = function ($req, $res, $next) {
            dumpFonction();
        };

        $app->add($mw);

        $app->get('/foo', function ($req, $res) {
            return $res;
        });

        $resOut = $app->run(true);

        $this->assertEquals(500, $resOut->getStatusCode());
        $this->assertNotRegExp('/.*middleware exception.*/', (string)$resOut);
    }

    public function appFactory(array $config = [])
    {
        if (empty($config)) {
            $config = include(__DIR__ . '/config.php');
        }
        return new App($config);
    }

    /**
     * @throws \Exception
     * @throws \Slender\Exception\MethodNotAllowedException
     * @throws \Slender\Exception\NotFoundException
     * @expectedException \Exception
     */
    public function testRunExceptionNoHandler()
    {
        $config = include(__DIR__ . '/config.php');
        unset($config['errorHandler']);
        $app = $this->appFactory($config);

        $app->get('/foo', function ($request, $response, $args) {
            return $response;
        });
        $app->add(function ($request, $response, $args) {
            throw new \Exception();
        });
        $response = $app->run(true);
    }

    /**
     * @requires PHP 7.0
     */
    public function testRunThrowable()
    {
        $app = $this->appFactory();
        $app->get('/foo', function ($req, $res, $args) {
            return $res;
        });
        $app->add(function ($req, $res, $args) {
            throw new \Error('Failed');
        });

        $res = $app->run(true);

        $res->getBody()->rewind();

        $this->assertSame(500, $res->getStatusCode());
        $this->assertSame('text/html', $res->getHeaderLine('Content-Type'));
        $this->assertEquals(0, strpos((string)$res->getBody(), '<html>'));
    }

    public function testRunNotFound()
    {
        $app = $this->appFactory();
        $app->get('/foo', function ($req, $res, $args) {
            return $res;
        });
        $app->add(function ($req, $res, $args) {
            throw new NotFoundException($req, $res);
        });
        $res = $app->run(true);

        $this->assertEquals(404, $res->getStatusCode());
    }

    /**
     * @expectedException \Slender\Exception\NotFoundException
     */
    public function testRunNotFoundWithoutHandler()
    {
        $config = include(__DIR__ . '/config.php');
        unset($config['notFoundHandler']);

        $app = $this->appFactory($config);

        $app->get('/foo', function ($req, $res, $args) {
            return $res;
        });
        $app->add(function ($req, $res, $args) {
            throw new NotFoundException($req, $res);
        });
        $res = $app->run(true);
    }



    public function testRunNotAllowed()
    {
        $app = $this->appFactory();
        $app->get('/foo', function ($req, $res, $args) {
            return $res;
        });
        $app->add(function ($req, $res, $args) {
            throw new MethodNotAllowedException($req, $res, ['POST']);
        });
        $res = $app->run(true);

        $this->assertEquals(405, $res->getStatusCode());
    }

    /**
     * @expectedException \Slender\Exception\MethodNotAllowedException
     */
    public function testRunNotAllowedWithoutHandler()
    {
        $config = include(__DIR__ . '/config.php');
        unset($config['notAllowedHandler']);

        $app = $this->appFactory($config);


        $app->get('/foo', function ($req, $res, $args) {
            return $res;
        });
        $app->add(function ($req, $res, $args) {
            throw new MethodNotAllowedException($req, $res, ['POST']);
        });
        $res = $app->run(true);
    }

    public function testAppRunWithdetermineRouteBeforeAppMiddleware()
    {
        $config = include(__DIR__ . '/config.php');
        $config['settings.determineRouteBeforeAppMiddleware'] = true;
        $app = $this->appFactory($config);

        $getHandler = new class
        {
            public function __invoke($request, $response)
            {
                return $response->write("Test");
            }
        };

        $app->get('/foo', $getHandler);

        $resOut = $app->run(true);
        $resOut->getBody()->rewind();
        $this->assertEquals("Test", $resOut->getBody()->getContents());
    }



    public function testExceptionErrorHandlerDisplaysErrorDetails()
    {
        $config = include(__DIR__ . '/config.php');
        $config['settings.displayErrorDetails'] = true;
        $app = $this->appFactory($config);

        $mw = function ($req, $res, $next) {
            throw new \RuntimeException('middleware exception');
        };

        $app->add($mw);

        $app->get('/foo', function ($req, $res) {
            return $res;
        });

        $resOut = $app->run(true);

        $this->assertEquals(500, $resOut->getStatusCode());
        $this->assertRegExp('/.*middleware exception.*/', (string)$resOut);
    }

    public function testFinalize()
    {
        $method = new \ReflectionMethod('Slender\App', 'finalize');
        $method->setAccessible(true);

        $response = new Response();
        $response->getBody()->write('foo');

        $response = $method->invoke($this->appFactory(), $response);

        $this->assertTrue($response->hasHeader('Content-Length'));
        $this->assertEquals('3', $response->getHeaderLine('Content-Length'));
    }

    public function testFinalizeWithoutBody()
    {
        $method = new \ReflectionMethod('Slender\App', 'finalize');
        $method->setAccessible(true);

        $response = $method->invoke($this->appFactory(), new Response(304));

        $this->assertFalse($response->hasHeader('Content-Length'));
        $this->assertFalse($response->hasHeader('Content-Type'));
    }

    public function testCallingAContainerCallable()
    {
        $config = include(__DIR__ . '/config.php');
        $config['foo'] = function () {
            return function ($x) {
                return $x;
            };
        };

        $app = $this->appFactory($config);

        $result = $app->foo('bar');
        $this->assertSame('bar', $result);

        $headers = new Headers();
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', Uri::createFromString(''), $headers, [], [], $body);
        $response = new Response();

        $response = $app->notFoundHandler($request, $response);

        $this->assertSame(404, $response->getStatusCode());
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testCallingFromContainerNotCallable()
    {
        $config = include(__DIR__ . '/config.php');
        $config += [
            'foo' => function ($c) {
                return null;
            }
        ];
        $app = $this->appFactory($config);
        $app->foo('bar');
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testCallingAnUnknownContainerCallableThrows()
    {
        $app = $this->appFactory();
        $app->foo('bar');
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testCallingAnUncallableContainerKeyThrows()
    {
        $app = $this->appFactory();
        $app->foo('bar');
    }

    public function testOmittingContentLength()
    {
        $method = new \ReflectionMethod('Slender\App', 'finalize');
        $method->setAccessible(true);

        $response = new Response();
        $response->getBody()->write('foo');

        $config = include(__DIR__ . '/config.php');
        $config['settings.addContentLengthHeader'] = false;
        $app = $this->appFactory($config);

        $response = $method->invoke($app, $response);

        $this->assertFalse($response->hasHeader('Content-Length'));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unexpected data in output buffer
     */
    public function testForUnexpectedDataInOutputBuffer()
    {
        $this->expectOutputString('test'); // needed to avoid risky test warning
        echo "test";
        $method = new \ReflectionMethod('Slender\App', 'finalize');
        $method->setAccessible(true);

        $response = new Response();
        $response->getBody()->write('foo');

        $config = include(__DIR__ . '/config.php');
        $config['settings']['addContentLengthHeader'] = true;
        $app = $this->appFactory($config);
        $response = $method->invoke($app, $response);
    }

    public function testUnsupportedMethodWithoutRoute()
    {
        $config = include(__DIR__ . '/config.php');
        $config['environment'] = Environment::mock(['REQUEST_URI' => '/', 'REQUEST_METHOD' => 'BADMTHD']);
        $app = $this->appFactory($config);

        $resOut = $app->run(true);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->assertEquals(404, $resOut->getStatusCode());
    }

    public function testUnsupportedMethodWithRoute()
    {
        $config = include(__DIR__ . '/config.php');
        $config['environment'] = Environment::mock(['REQUEST_URI' => '/', 'REQUEST_METHOD' => 'BADMTHD']);

        $app = $this->appFactory($config);
        $app->get('/', function () {
            // stubbed action to give us a route at /
        });

        $resOut = $app->run(true);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->assertEquals(405, $resOut->getStatusCode());
    }

    public function testInvalidMethodWithRoute()
    {
        $this->expectException(InvalidMethodException::class);

        $config = include(__DIR__ . '/config.php');
        $config['environment'] = Environment::mock(['REQUEST_URI' => '/', 'REQUEST_METHOD' => 'B@DMTHD']);

        $app = $this->appFactory($config);
        $app->get('/', function () {
            // stubbed action to give us a route at /
        });

        $resOut = $app->run(true);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->assertEquals(405, $resOut->getStatusCode());
    }

    public function testContainerSetToRoute()
    {
        $app = $this->appFactory();
        $container = $app->getContainer();

        /** @var $router Router */
        $router = $container->get('router');
        $router->map(['get'], '/foo', new class {
            function __invoke($rq, $rs, $z)
            {
                $response = json_encode(['name'=>'bar', 'arguments' => []]);
                return $rs->getBody()->write($response);
            }
        });

        // Invoke app
        $req = $container->get('request');
        $res = $container->get('response');
        $resOut = $app($req, $res);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals(json_encode(['name'=>'bar', 'arguments' => []]), (string)$res->getBody());
    }

    public function testIsEmptyResponseWithEmptyMethod()
    {
        $method = new \ReflectionMethod('Slender\App', 'isEmptyResponse');
        $method->setAccessible(true);

        $response = new Response();
        $response = $response->withStatus(204);

        $result = $method->invoke($this->appFactory(), $response);
        $this->assertTrue($result);
    }

    public function testIsEmptyResponseWithoutEmptyMethod()
    {
        $method = new \ReflectionMethod('Slender\App', 'isEmptyResponse');
        $method->setAccessible(true);

        /** @var Response $response */
        $response = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $response->method('getStatusCode')
            ->willReturn(204);

        $result = $method->invoke($this->appFactory(), $response);
        $this->assertTrue($result);
    }
}
