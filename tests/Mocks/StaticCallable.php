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

/**
* Mock object for Slender\Tests\RouteTest
*/
class StaticCallable
{
    public static function run($req, $res, $next)
    {
        $res->write('In1');
        $res = $next($req, $res);
        $res->write('Out1');

        return $res;
    }
}
