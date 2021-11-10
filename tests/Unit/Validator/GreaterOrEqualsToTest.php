<?php

declare(strict_types=1);

namespace OpenIDConnectClient\Tests\Unit\Validator;

use OpenIDConnectClient\Validator\GreaterOrEqualsTo;

final class GreaterOrEqualsToTest extends AbstractValidatorTester
{
    protected static string $classUnderTest = GreaterOrEqualsTo::class;

    public function isValidArgumentExpectationsProvider(): iterable
    {
        yield ['123', '122', false];
        yield [123, '122', false];
        yield ['123', 122, false];
        yield [123, 122, false];
        yield [123, null, false];

        yield [-5, -5, true];

        yield ['122', '123', true];
        yield ['122', 123, true];
        yield [122, '123', true];
        yield [122, 123, true];
        yield [null, 123, true];
    }

    public function isValidInvalidArgumentProvider(): iterable
    {
        yield [123, -2.3];
        yield [123, 'some string'];
        yield [123, (object)[]];
        yield [123, 12.2];
        yield [123, 12.002];
        yield [123, []];
        yield [123, 123.0011];
        yield [123, 123.0010];

        yield [-2.3, 123];
        yield ['some string', 123];
        yield [(object)[], 123];
        yield [12.2, 123];
        yield [12.002, 123];
        yield [[], 123];
        yield [123.0011, 123];
        yield [123.0010, 123];
    }
}
