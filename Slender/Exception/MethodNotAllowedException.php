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
namespace Slender\Exception;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class MethodNotAllowedException extends SlenderThrowable
{
    /**
     * HTTP methods allowed
     *
     * @var string[]
     */
    protected $allowedMethods;

    /**
     * Create new exception
     */
    public function __construct(ServerRequestInterface $request, ResponseInterface $response, array $allowedMethods)
    {
        parent::__construct($request, $response);
        $this->allowedMethods = $allowedMethods;
    }

    /**
     * Get allowed methods
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
