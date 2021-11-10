<?php

declare(strict_types=1);

namespace OpenIDConnectClient\Validator;

final class NotEmpty implements ValidatorInterface
{
    use ValidatorTrait;

    public function isValid($expectedValue, $actualValue): bool
    {
        if (empty($actualValue)) {
            $this->message = sprintf('%s is required and cannot be empty', $this->getName());

            return false;
        }

        return true;
    }
}
