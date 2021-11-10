<?php

declare(strict_types=1);

namespace OpenIDConnectClient\Tests\Unit\Validator;

use OpenIDConnectClient\Validator\NotEmpty;

final class NotEmptyTest extends AbstractValidatorTester
{
    protected static string $classUnderTest = NotEmpty::class;

    public function isValidArgumentExpectationsProvider(): iterable
    {
        yield [null, 'not empty', true];
        yield [null, ' ', true];
        yield [null, "\n", true];
        yield [null, "\n\t", true];
        yield [null, [1], true];
        yield [null, ['array' => 1], true];
        yield [null, (object)['field' => 1], true];
        yield [null, 123, true];
        yield [null, 12.3, true];
        yield [null, true, true];

        yield [null, '', false];
        yield [null, null, false];
        yield [null, [], false];
        yield [null, 0, false];
        yield [null, false, false];
    }

    public function isValidInvalidArgumentProvider(): iterable
    {
        // NotEmpty can process any type
        return [];
    }
}
