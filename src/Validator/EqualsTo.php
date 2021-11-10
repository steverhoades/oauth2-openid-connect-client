<?php

declare(strict_types=1);

namespace OpenIDConnectClient\Validator;

use Webmozart\Assert\Assert;

final class EqualsTo implements ValidatorInterface
{
    use ValidatorTrait;

    /**
     * @param mixed $expectedValue
     * @param mixed $actualValue
     */
    public function isValid($expectedValue, $actualValue): bool
    {
        Assert::nullOrScalar($expectedValue);
        Assert::nullOrScalar($actualValue);

        if ($expectedValue === $actualValue) {
            return true;
        }

        $this->message = sprintf('%s is invalid as it does not equal expected %s', $actualValue, $expectedValue);

        return false;
    }
}
