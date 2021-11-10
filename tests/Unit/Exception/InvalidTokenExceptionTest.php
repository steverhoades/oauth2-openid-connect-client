<?php

declare(strict_types=1);

namespace OpenIDConnectClient\Tests\Unit\Exception;

use OpenIDConnectClient\Exception\InvalidTokenException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class InvalidTokenExceptionTest extends TestCase
{
    public function testInvalidTokenExceptionConstructor(): void
    {
        $exception = new InvalidTokenException('some exception message', 123);

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertSame('some exception message', $exception->getMessage());
        self::assertSame(123, $exception->getCode());
    }
}
