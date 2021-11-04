<?php

declare(strict_types=1);

namespace OpenIDConnectClient\Tests\Unit\Validator;

use OpenIDConnectClient\Validator\EqualsToOrContains;

final class EqualsToOrContainsTest extends AbstractValidatorTester
{
    protected static string $classUnderTest = EqualsToOrContains::class;

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

        yield [1, [1, 2], true];
        yield ['some string', ['other string', 'some string'], true];
        yield [123.456, [1, 2, 3, 4, 5, 6, 123.456], true];
        yield [true, [456, true], true];
        yield [false, [456, false], true];

        yield [1, [2], false];
        yield ['some string', ['other string', 'some string 2'], false];
        yield [123.456, [1, 2, 3, 4, 5, 6, 123.4567], false];
        yield [true, [456, 'true'], false];
        yield [false, [456, 'false'], false];
        yield [0, [456, false], false];
    }

    public function isValidInvalidArgumentProvider(): iterable
    {
        yield [(object)['field' => 1], ''];
        yield [['array' => 1], ['array' => 1]];
        yield [[1], [1]];
        yield [[], []];
    }
}
