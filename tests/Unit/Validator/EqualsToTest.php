<?php

declare(strict_types=1);

namespace OpenIDConnectClient\Tests\Unit\Validator;

use OpenIDConnectClient\Validator\EqualsTo;

final class EqualsToTest extends AbstractValidatorTester
{
    protected static string $classUnderTest = EqualsTo::class;

    public function isValidArgumentExpectationsProvider(): iterable
    {
        yield ["\n", "\n", true];
        yield ["\n\t", "\n\t", true];
        yield [' ', ' ', true];
        yield ['', '', true];
        yield ['not empty', 'not empty', true];
        yield [0, 0, true];
        yield [123, 123, true];
        yield [12.3, 12.3, true];
        yield [false, false, true];
        yield [null, null, true];
        yield [true, true, true];

        yield [null, "\n", false];
        yield [null, "\n\t", false];
        yield [null, ' ', false];
        yield [null, '', false];
        yield [null, 'not empty', false];
        yield [null, 0, false];
        yield [null, 123, false];
        yield [null, 12.3, false];
        yield [null, false, false];
        yield [null, false, false];
        yield [null, true, false];
    }

    public function isValidInvalidArgumentProvider(): iterable
    {
        yield [(object)['field' => 1], (object)['field' => 1], true];
        yield [['array' => 1], ['array' => 1], true];
        yield [[1], [1], true];
        yield [[], [], true];
    }
}
