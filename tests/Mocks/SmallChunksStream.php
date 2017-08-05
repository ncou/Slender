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

use Psr\Http\Message\StreamInterface;

/**
 * A mock stream interface that yields small chunks when reading
 */
class SmallChunksStream implements StreamInterface
{
    const CHUNK_SIZE = 10;
    const SIZE = 40;

    /**
     * @var int
     */
    private $amountToRead;

    public function __construct()
    {
        $this->amountToRead = self::SIZE;
    }

    public function __toString(): string
    {
        throw new \Exception('not implemented');
    }

    public function close()
    {
    }

    public function detach()
    {
        throw new \Exception('not implemented');
    }

    public function eof(): bool
    {
        return $this->amountToRead === 0;
    }

    public function getContents(): string
    {
        throw new \Exception('not implemented');
    }

    public function getMetadata(?string $key = null)
    {
        throw new \Exception('not implemented');
    }

    public function getSize(): ?int
    {
        return self::SIZE ?? null;
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function isSeekable(): bool
    {
        return false;
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function read(int $length): string
    {
        $size = min($this->amountToRead, self::CHUNK_SIZE, $length);
        $this->amountToRead -= $size;

        return str_repeat('.', min($length, $size));
    }

    public function rewind(): void
    {
        throw new \Exception('not implemented');
    }

    public function seek(int $offset, int $whence = SEEK_SET)
    {
        throw new \Exception('not implemented');
    }

    public function tell(): int
    {
        throw new \Exception('not implemented');
    }

    public function write(string $string): int
    {
        return strlen($string);
    }
}
