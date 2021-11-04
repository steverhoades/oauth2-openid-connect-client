<?php

declare(strict_types=1);

namespace OpenIDConnectClient\Validator;

use Webmozart\Assert\Assert;

final class EqualsToOrContains implements ValidatorInterface
{
    use ValidatorTrait;

    public function isValid($expectedValue, $actualValue): bool
    {
        Assert::nullOrScalar($expectedValue);

        if (!is_array($actualValue)) {
            Assert::nullOrScalar($actualValue);

            $valid = $expectedValue === $actualValue;
            if (!$valid) {
                $this->message = sprintf(
                    '%s is invalid as it does not equal expected %s',
                    $actualValue,
                    $expectedValue,
                );
            }

            return $valid;
        }

        $valid = in_array($expectedValue, $actualValue, true);
        if (!$valid) {
            $this->message = sprintf(
                'The value is invalid as the given array does not contain expected %s',
                $expectedValue,
            );
        }

        return $valid;
    }
}
